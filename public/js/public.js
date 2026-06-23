/* global jQuery */
jQuery( document ).ready( function ( $ ) {
	'use strict';

	// Builds a REST request URL safe for both pretty permalinks
	// (".../wp-json/jetprayer/v1/timings") and the ?rest_route= fallback
	// (".../index.php?rest_route=/jetprayer/v1/timings") - the latter already
	// has a "?" in restBase, so a plain `${restBase}${path}?${query}` would
	// produce a second "?" and break routing.
	function jpBuildRestUrl( restBase, path, params ) {
		const base = restBase + path;
		const query = Object.keys( params )
			.map( function ( key ) {
				return (
					encodeURIComponent( key ) +
					'=' +
					encodeURIComponent( params[ key ] )
				);
			} )
			.join( '&' );
		const separator = base.indexOf( '?' ) === -1 ? '?' : '&';
		return base + separator + query;
	}

	// Monthly Timetable Modal Overlay Controls (Scoped to local siblings to support multiple widgets)
	$( document ).on( 'click', '.jp-modal-open-btn', function () {
		$( this )
			.closest( '.jp-modal-trigger-wrapper' )
			.siblings( '.jp-modal-overlay' )
			.removeClass( 'hidden' );
	} );

	$( document ).on( 'click', '.jp-modal-close', function () {
		$( this ).closest( '.jp-modal-overlay' ).addClass( 'hidden' );
	} );

	$( document ).on( 'click', '.jp-modal-overlay', function ( e ) {
		if ( $( e.target ).is( '.jp-modal-overlay' ) ) {
			$( e.target ).addClass( 'hidden' );
		}
	} );

	// Dynamic City Switcher Event
	$( document ).on( 'change', '.jp-city-switcher', function () {
		const select = $( this );
		const val = select.val();
		if ( ! val ) {
			return;
		}

		const parts = val.split( '|' );
		const city = parts[ 0 ];
		const country = parts[ 1 ] || '';
		const methodId = parts[ 2 ] || '';

		const wrapper = select.closest( '[data-layout]' );
		const layout = wrapper.data( 'layout' );
		const date = wrapper.data( 'date' );

		// Update data attributes on wrapper immediately
		wrapper.data( 'city', city );
		wrapper.data( 'country', country );
		wrapper.attr( 'data-city', city );
		wrapper.attr( 'data-country', country );

		if ( methodId ) {
			wrapper.data( 'method', methodId );
			wrapper.attr( 'data-method', methodId );
		}
		const method = wrapper.data( 'method' );

		// 1. Fetch single date timings for card/grid/slider/ticker/modal mini-card
		const restBase =
			( window.jetprayerPublic && window.jetprayerPublic.restUrl ) ||
			'/wp-json/jetprayer/v1';
		const url = jpBuildRestUrl( restBase, '/timings', {
			start_date: date,
			end_date: date,
			method_id: method,
			city,
			country,
		} );

		$.ajax( {
			url,
			method: 'GET',
			success( response ) {
				if (
					response.success &&
					response.data &&
					response.data.length > 0
				) {
					const timings = response.data[ 0 ];

					// Update Title / Location name
					const locationName =
						timings.city +
						( timings.country ? ', ' + timings.country : '' );
					wrapper
						.find( '.jp-title, .jp-ticker-title' )
						.each( function () {
							const el = $( this );
							// Only update static text, don't overwrite if it contains the switcher
							if (
								el.find( '.jp-city-switcher' ).length === 0 &&
								! el.hasClass( 'jp-city-switcher' )
							) {
								el.text( locationName );
							}
						} );

					// Update Hijri Date
					wrapper
						.find( '.jp-hijri-date, .jp-ticker-hijri' )
						.text( timings.hijri_date );

					// Update each prayer time
					const prayers = [
						'imsak',
						'fajr',
						'sunrise',
						'dhuhr',
						'asr',
						'maghrib',
						'isha',
					];
					prayers.forEach( function ( p ) {
						const timeVal = timings[ p ];
						if ( timeVal ) {
							// For card/grid/slider
							wrapper
								.find(
									`[data-prayer="${ p }"] .jp-prayer-time, [data-prayer="${ p }"] .jp-grid-time, [data-prayer="${ p }"] .jp-slider-time`
								)
								.text( timeVal );
							// For ticker
							wrapper
								.find(
									`.jp-ticker-item[data-prayer="${ p }"] .jp-ticker-item-time`
								)
								.text( timeVal );
						}
					} );

					// Update Next Prayer Highlight
					highlightNextPrayer( wrapper, timings );
				}
			},
		} );

		// 2. Fetch monthly range timings if modal layout is active, to keep the table in sync
		if ( layout === 'modal' ) {
			const overlay = wrapper.siblings( '.jp-modal-overlay' );

			if ( overlay.length ) {
				// Update modal title
				overlay
					.find( '.jp-modal-header h4' )
					.text(
						`Monthly Prayer Timetable - ${ city }${
							country ? ', ' + country : ''
						}`
					);

				// Calculate start & end of month
				const year = date.substring( 0, 4 );
				const month = date.substring( 5, 7 );
				const lastDay = new Date( year, month, 0 ).getDate();
				const formattedLastDay = lastDay < 10 ? '0' + lastDay : lastDay;
				const startDate = `${ year }-${ month }-01`;
				const endDate = `${ year }-${ month }-${ formattedLastDay }`;

				const rangeRestBase =
					( window.jetprayerPublic &&
						window.jetprayerPublic.restUrl ) ||
					'/wp-json/jetprayer/v1';
				const rangeUrl = jpBuildRestUrl( rangeRestBase, '/timings', {
					start_date: startDate,
					end_date: endDate,
					method_id: method,
					city,
					country,
				} );

				$.ajax( {
					url: rangeUrl,
					method: 'GET',
					success( response ) {
						if ( response.success && response.data ) {
							const tbody = overlay.find(
								'.jp-modal-table tbody'
							);
							tbody.empty();

							response.data.forEach( function ( row ) {
								const isToday =
									row.prayer_date ===
									new Date().toISOString().slice( 0, 10 );
								const rowClass = isToday
									? 'jp-modal-today-row'
									: '';

								// Format date display
								const rowDate = new Date( row.prayer_date );
								const dayNum = String(
									rowDate.getDate()
								).padStart( 2, '0' );
								const monthAbbr = rowDate.toLocaleString(
									'default',
									{ month: 'short' }
								);

								// Build row cells based on header columns
								let tr = `<tr class="${ rowClass }" data-date="${ row.prayer_date }">`;
								tr += `<td class="font-mono"><strong>${ dayNum }</strong> <small>${ monthAbbr }</small></td>`;

								// Find the table header keys
								overlay
									.find( '.jp-modal-table th' )
									.each( function ( index ) {
										if ( index > 0 ) {
											const prayerKey =
												$( this ).data( 'prayer' );
											const cellVal =
												row[ prayerKey ] || '';
											tr += `<td class="font-mono" data-prayer="${ prayerKey }">${ cellVal }</td>`;
										}
									} );

								tr += '</tr>';
								tbody.append( tr );
							} );
						}
					},
				} );
			}
		}
	} );

	// JS Helper to Highlight Next Prayer
	function highlightNextPrayer( wrapper, timings ) {
		const now = new Date();
		const currentHours = String( now.getHours() ).padStart( 2, '0' );
		const currentMinutes = String( now.getMinutes() ).padStart( 2, '0' );
		const currentTimeStr = `${ currentHours }:${ currentMinutes }`;
		const prayers = [
			'imsak',
			'fajr',
			'sunrise',
			'dhuhr',
			'asr',
			'maghrib',
			'isha',
		];

		let nextPrayer = 'imsak';
		for ( let i = 0; i < prayers.length; i++ ) {
			const p = prayers[ i ];
			if ( timings[ p ] ) {
				const timeVal = timings[ p ].match( /\d{2}:\d{2}/ );
				if ( timeVal && timeVal[ 0 ] > currentTimeStr ) {
					nextPrayer = p;
					break;
				}
			}
		}

		// Update highlighting classes
		wrapper
			.find( '.jp-prayer-item, .jp-grid-item, .jp-slider-item' )
			.removeClass( 'jp-next-prayer' );
		wrapper
			.find( `[data-prayer="${ nextPrayer }"]` )
			.addClass( 'jp-next-prayer' );

		// Update Badge
		wrapper
			.find( '.jp-next-badge' )
			.addClass( 'hidden' )
			.attr( 'hidden', true );
		wrapper
			.find( `[data-prayer="${ nextPrayer }"] .jp-next-badge` )
			.removeClass( 'hidden' )
			.removeAttr( 'hidden' );

		// Update Grid Indicator
		wrapper
			.find( '.jp-next-indicator' )
			.addClass( 'hidden' )
			.attr( 'hidden', true );
		wrapper
			.find( `[data-prayer="${ nextPrayer }"] .jp-next-indicator` )
			.removeClass( 'hidden' )
			.removeAttr( 'hidden' );
	}

	// Highlight next prayer on initial page load for all widgets (handles static caching issues)
	function initNextPrayerHighlighting() {
		$( '[data-layout]' ).each( function () {
			const wrapper = $( this );
			const timings = {};

			// Extract timings from the DOM
			wrapper.find( '[data-prayer]' ).each( function () {
				const item = $( this );
				const prayerKey = item.data( 'prayer' );
				if ( ! prayerKey ) {
					return;
				}

				// Read the time text
				let timeText = '';
				if ( wrapper.data( 'layout' ) === 'ticker' ) {
					timeText = item.find( '.jp-ticker-item-time' ).text();
				} else {
					timeText = item
						.find(
							'.jp-prayer-time, .jp-grid-time, .jp-slider-time'
						)
						.text();
				}

				if ( timeText ) {
					timings[ prayerKey ] = timeText.trim();
				}
			} );

			if ( Object.keys( timings ).length > 0 ) {
				highlightNextPrayer( wrapper, timings );
			}
		} );
	}

	// Run on initial page load
	initNextPrayerHighlighting();

	// Slider/Carousel scroll controls
	$( '.jp-slider-layout' ).each( function () {
		const container = $( this );
		const track = container.find( '.jp-slider-track' );
		const items = container.find( '.jp-slider-item' );
		const viewport = container.find( '.jp-slider-viewport' );
		const prevBtn = container.find( '.jp-slider-prev' );
		const nextBtn = container.find( '.jp-slider-next' );

		let currentPosition = 0;
		const itemWidth = 130 + 15; // Width of 130px + gap of 15px

		function getMaxScroll() {
			return Math.max(
				0,
				items.length * itemWidth - viewport.width() + 15
			);
		}

		nextBtn.on( 'click', function () {
			const maxScroll = getMaxScroll();
			currentPosition += itemWidth;
			if ( currentPosition > maxScroll ) {
				currentPosition = maxScroll;
			}
			track.css( 'transform', 'translateX(-' + currentPosition + 'px)' );
		} );

		prevBtn.on( 'click', function () {
			currentPosition -= itemWidth;
			if ( currentPosition < 0 ) {
				currentPosition = 0;
			}
			track.css( 'transform', 'translateX(-' + currentPosition + 'px)' );
		} );

		// Adjust scroll position during window resizing
		$( window ).on( 'resize', function () {
			const maxScroll = getMaxScroll();
			if ( currentPosition > maxScroll ) {
				currentPosition = maxScroll;
				track.css(
					'transform',
					'translateX(-' + currentPosition + 'px)'
				);
			}
		} );
	} );
} );

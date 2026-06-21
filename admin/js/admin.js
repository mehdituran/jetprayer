jQuery( document ).ready( function ( $ ) {
	'use strict';

	// REST details from localized script
	const restUrl = jetprayerAdmin.restUrl;
	const restNonce = jetprayerAdmin.nonce;
	const i18n = jetprayerAdmin.i18n;
	const currentCountry = jetprayerAdmin.currentCountry || '';
	const currentCity = jetprayerAdmin.currentCity || '';

	// Safe client-side sprintf translation utility helper
	function sprintf( format, ...args ) {
		let i = 0;
		return format.replace( /%([1-9]\$)?([sd])/g, function ( match, pos ) {
			if ( pos ) {
				const index = parseInt( pos.charAt( 0 ), 10 ) - 1;
				return typeof args[ index ] !== 'undefined'
					? args[ index ]
					: match;
			}
			return typeof args[ i ] !== 'undefined' ? args[ i++ ] : match;
		} );
	}

	// Pagination and loaded year state
	let jpCurrentMode = 'month';
	let jpLoadedTimings = [];
	let jpActiveMonth = '01';

	// Displays tab state: per-layout unsaved-changes flag, keyed by layout slug
	const jpDisplayDirty = {};
	let jpColorPickersInitialized = false;

	// Populate Countries Autocomplete
	const countryInput = $( '#jp_country' );
	const countryDatalist = $( '#jp-country-list' );
	if (
		countryInput.length &&
		countryDatalist.length &&
		typeof jetprayerCountriesData !== 'undefined'
	) {
		jetprayerCountriesData.countries.forEach( function ( c ) {
			countryDatalist.append( '<option value="' + c + '">' );
		} );

		// Set initial values
		countryInput.val( currentCountry );
		populateCities( currentCountry, currentCity );
	}

	// Country selection/change handler
	let lastCountry = currentCountry;
	countryInput.on( 'input change', function () {
		const selectedCountry = $( this ).val();
		if ( selectedCountry !== lastCountry ) {
			lastCountry = selectedCountry;
			$( '#jp_city' ).val( '' ); // Clear old city value
			if (
				typeof jetprayerCountriesData !== 'undefined' &&
				jetprayerCountriesData.countries.includes( selectedCountry )
			) {
				populateCities( selectedCountry, '' );
			} else {
				$( '#jp-city-list' ).empty();
			}
		}
	} );

	function populateCities( country, selectCity ) {
		const cityDatalist = $( '#jp-city-list' );
		cityDatalist.empty();

		if (
			typeof jetprayerCountriesData !== 'undefined' &&
			jetprayerCountriesData.cities[ country ]
		) {
			const cityArray = jetprayerCountriesData.cities[ country ];
			cityArray.forEach( function ( city ) {
				cityDatalist.append( '<option value="' + city + '">' );
			} );
			if ( selectCity ) {
				$( '#jp_city' ).val( selectCity );
			}
		}
	}

	// Tabs Handler
	$( '.jetprayer-tabs a' ).on( 'click', function ( e ) {
		e.preventDefault();
		const tab = $( this ).data( 'tab' );

		// Leaving the Displays tab with an unsaved sub-tab? Confirm first.
		if (
			$( '#jetprayer-tab-displays' ).hasClass( 'active' ) &&
			'displays' !== tab
		) {
			const activeLayout = $( '.jp-display-panel.active' ).data(
				'layout'
			);
			if ( activeLayout && jpDisplayDirty[ activeLayout ] ) {
				if ( ! confirm( i18n.confirmCancel ) ) {
					return;
				}
				jpDisplayDirty[ activeLayout ] = false;
			}
		}

		$( '.nav-tab' ).removeClass( 'nav-tab-active' );
		$( this ).addClass( 'nav-tab-active' );

		$( '.jetprayer-tab-content' ).removeClass( 'active' );
		$( '#jetprayer-tab-' + tab ).addClass( 'active' );

		if ( 'editor' === tab ) {
			jpCurrentMode = 'month';
			jpLoadedTimings = [];
			loadSyncedFilters();
			const tableBody = $( '#jetprayer-table-body' );
			const placeholderText =
				i18n && i18n.clickToLoad
					? i18n.clickToLoad
					: 'Click "Load Month" or "Load Year" to display database rows.';
			tableBody.html(
				`<tr><td colspan="13" class="text-center">${ placeholderText }</td></tr>`
			);
			$( '#jp-pagination-container' ).addClass( 'hidden' ).empty();
		}

		if ( 'displays' === tab && ! jpColorPickersInitialized ) {
			jpColorPickersInitialized = true;
			initDisplayColorPickers();
		}
	} );

	// Displays tab: sub-tab (layout) switching
	$( '.jp-subtab-link' ).on( 'click', function ( e ) {
		e.preventDefault();
		const targetLayout = $( this ).data( 'sublayout' );
		const currentLayout = $( '.jp-display-panel.active' ).data( 'layout' );

		if (
			currentLayout &&
			currentLayout !== targetLayout &&
			jpDisplayDirty[ currentLayout ]
		) {
			if ( ! confirm( i18n.confirmCancel ) ) {
				return;
			}
			jpDisplayDirty[ currentLayout ] = false;
		}

		$( '.jp-subtab-link' ).removeClass( 'jp-subtab-active' );
		$( this ).addClass( 'jp-subtab-active' );

		$( '.jp-display-panel' ).removeClass( 'active' );
		$( '#jp-display-panel-' + targetLayout ).addClass( 'active' );
	} );

	// Displays tab: live range slider value readout
	$( '.jp-range-field' ).on( 'input', function () {
		$( this )
			.closest( '.jp-form-group' )
			.find( '.jp-range-value' )
			.text( $( this ).val() );
	} );

	// Displays tab: mark the relevant layout dirty on any field change
	$( '.jp-display-form' ).each( function () {
		const layout = $( this ).data( 'layout' );
		$( this )
			.find( 'input, select' )
			.on( 'change input', function () {
				jpDisplayDirty[ layout ] = true;
			} );
	} );

	// Lazily initialize the native WordPress color picker only once the Displays tab is opened
	function initDisplayColorPickers() {
		$( '.jp-display-form' ).each( function () {
			const layout = $( this ).data( 'layout' );
			$( this )
				.find( '.jp-color-field' )
				.wpColorPicker( {
					change: function () {
						jpDisplayDirty[ layout ] = true;
					},
					clear: function () {
						jpDisplayDirty[ layout ] = true;
					},
				} );
		} );
	}

	// Displays tab: per-layout Save Display Settings submit
	$( '.jp-display-form' ).on( 'submit', function ( e ) {
		e.preventDefault();
		const form = $( this );
		const layout = form.data( 'layout' );
		const submitBtn = form.find( 'button[type="submit"]' );
		const originalLabel = submitBtn.text();
		const settings = {};

		form.find( 'input[type="checkbox"]' ).each( function () {
			settings[ $( this ).attr( 'name' ) ] = $( this ).is( ':checked' )
				? 1
				: 0;
		} );
		form.find(
			'input.jp-color-field, input.jp-range-field, input[type="text"], select'
		).each( function () {
			const name = $( this ).attr( 'name' );
			if ( name ) {
				settings[ name ] = $( this ).val();
			}
		} );

		submitBtn.prop( 'disabled', true ).text( i18n.saving );

		$.ajax( {
			url: restUrl + '/display-settings',
			method: 'POST',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', restNonce );
			},
			data: { layout: layout, settings: settings },
			success: function ( response ) {
				alert( response.message || i18n.saved );
				jpDisplayDirty[ layout ] = false;
			},
			error: function ( xhr ) {
				console.error( xhr );
				alert( i18n.error );
			},
			complete: function () {
				submitBtn.prop( 'disabled', false ).text( originalLabel );
			},
		} );
	} );

	// Toggle location settings input fields
	$( 'input[name="type"]' ).on( 'change', function () {
		const val = $( this ).val();
		if ( 'city' === val ) {
			$( '.location-fields-city' ).removeClass( 'hidden' );
			$( '.location-fields-coords' ).addClass( 'hidden' );
		} else {
			$( '.location-fields-city' ).addClass( 'hidden' );
			$( '.location-fields-coords' ).removeClass( 'hidden' );
		}
	} );

	// Save Settings & Trigger Sync Form Submit
	$( '#jetprayer-settings-form' ).on( 'submit', function ( e ) {
		e.preventDefault();
		const btn = $( '#jetprayer-sync-btn' );
		const year = $( '#jp_sync_year' ).val();
		const logBox = $( '#jetprayer-sync-log' );

		const formData = {
			year: year,
			type: $( 'input[name="type"]:checked' ).val(),
			city: $( '#jp_city' ).val(),
			country: $( '#jp_country' ).val(),
			latitude: $( '#jp_latitude' ).val(),
			longitude: $( '#jp_longitude' ).val(),
			method: $( '#jp_method' ).val(),
			school: $( '#jp_school' ).val(),
			timezone: $( '#jp_timezone' ).val(),
		};

		btn.prop( 'disabled', true ).addClass( 'loading' );
		logBox
			.removeClass( 'hidden' )
			.html( '<p class="info">' + i18n.syncing + '</p>' );

		$.ajax( {
			url: restUrl + '/sync',
			method: 'POST',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', restNonce );
			},
			data: formData,
			success: function ( response ) {
				logBox.html(
					'<p class="success">' + response.message + '</p>'
				);
				// Update last sync time label
				const now = new Date();
				const formattedTime =
					now.getFullYear() +
					'-' +
					String( now.getMonth() + 1 ).padStart( 2, '0' ) +
					'-' +
					String( now.getDate() ).padStart( 2, '0' ) +
					' ' +
					String( now.getHours() ).padStart( 2, '0' ) +
					':' +
					String( now.getMinutes() ).padStart( 2, '0' ) +
					':' +
					String( now.getSeconds() ).padStart( 2, '0' );
				$( '#jetprayer-sync-time' ).text( formattedTime );
				$( '.status-indicator' )
					.removeClass( 'pending' )
					.addClass( 'synced' );
				refreshSyncedMethods();
				loadSyncedFilters();
			},
			error: function ( xhr ) {
				console.error( xhr );
				let errMsg = i18n.error;
				if ( xhr.responseJSON && xhr.responseJSON.message ) {
					errMsg = xhr.responseJSON.message;
				}
				logBox.html( '<p class="error">Error: ' + errMsg + '</p>' );
			},
			complete: function () {
				btn.prop( 'disabled', false ).removeClass( 'loading' );
			},
		} );
	} );

	// Fetch and render the list of locations and methods that already have synced data
	function refreshSyncedMethods() {
		const listBox = $( '#jetprayer-synced-methods-list' );
		if ( ! listBox.length ) {
			return;
		}

		$.ajax( {
			url: restUrl + '/synced-locations',
			method: 'GET',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', restNonce );
			},
			success: function ( response ) {
				const locations = ( response && response.data ) || [];
				if ( ! Array.isArray( locations ) || ! locations.length ) {
					listBox.html(
						'<span class="jetprayer-no-sync">' +
							( ( i18n && i18n.noSyncedMethods ) ||
								'No data synced yet.' ) +
							'</span>'
					);
					return;
				}

				const selectElement = $(
					'<select class="widefat jp-synced-methods-select" style="margin-top: 10px;"></select>'
				);

				const count = locations.length;
				const label = 'Locations in Database (' + count + ')';
				selectElement.append(
					$( '<option value=""></option>' ).text( label )
				);

				locations.forEach( function ( loc ) {
					if (
						typeof loc === 'object' &&
						loc !== null &&
						loc.city &&
						loc.country &&
						typeof loc.method_id !== 'undefined'
					) {
						const text =
							loc.city +
							', ' +
							loc.country +
							' (' +
							loc.method_id +
							')';
						selectElement.append(
							$( '<option disabled></option>' ).text( text )
						);
					}
				} );

				listBox.html( selectElement );
			},
		} );
	}

	// Load timings list for CRUD Editor (Single Month)
	$( '#jp-load-timings' ).on( 'click', function () {
		jpCurrentMode = 'month';
		jpLoadedTimings = [];
		$( '#jp-pagination-container' ).addClass( 'hidden' ).empty();
		loadTimings();
	} );

	function loadTimings() {
		const year = $( '#jp_edit_year' ).val();
		const month = $( '#jp_edit_month' ).val();
		const methodId = $( '#jp_edit_method' ).val();
		const country = $( '#jp_edit_country' ).val();
		const city = $( '#jp_edit_city' ).val();
		const tableBody = $( '#jetprayer-table-body' );

		tableBody.html(
			`<tr><td colspan="14" class="text-center">${
				i18n.loading || 'Loading timings...'
			}</td></tr>`
		);
		$( '#jp-pagination-container' ).addClass( 'hidden' ).empty();

		const daysInMonth = new Date( year, month, 0 ).getDate();
		const start_date = `${ year }-${ month }-01`;
		const end_date = `${ year }-${ month }-${ String(
			daysInMonth
		).padStart( 2, '0' ) }`;

		$.ajax( {
			url: restUrl + '/timings',
			method: 'GET',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', restNonce );
			},
			data: {
				start_date: start_date,
				end_date: end_date,
				method_id: methodId,
				city: city,
				country: country,
			},
			success: function ( response ) {
				if ( response.success && response.data.length > 0 ) {
					renderTimingsTable( response.data );
				} else {
					tableBody.html(
						`<tr><td colspan="14" class="text-center">${
							i18n.noMethodData ||
							'No database timings found for this calculation method. Please go to Settings & Sync tab, select this method, click Save Settings, and run the Year Synchronization first.'
						}</td></tr>`
					);
				}
			},
			error: function ( xhr ) {
				console.error( xhr );
				tableBody.html(
					`<tr><td colspan="13" class="text-center error">${
						i18n.failedLoad ||
						'Failed to load timings database rows.'
					}</td></tr>`
				);
			},
		} );
	}

	// Load timings for CRUD Editor (Entire Year)
	$( '#jp-load-year-timings' ).on( 'click', function () {
		loadYearTimings();
	} );

	function loadYearTimings() {
		jpCurrentMode = 'year';
		const year = $( '#jp_edit_year' ).val();
		const methodId = $( '#jp_edit_method' ).val();
		const country = $( '#jp_edit_country' ).val();
		const city = $( '#jp_edit_city' ).val();
		const tableBody = $( '#jetprayer-table-body' );
		const paginationContainer = $( '#jp-pagination-container' );

		tableBody.html(
			`<tr><td colspan="14" class="text-center">${
				i18n.loadingYear || 'Loading entire year. Please wait...'
			}</td></tr>`
		);
		paginationContainer.addClass( 'hidden' ).empty();

		const start_date = `${ year }-01-01`;
		const end_date = `${ year }-12-31`;

		$.ajax( {
			url: restUrl + '/timings',
			method: 'GET',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', restNonce );
			},
			data: {
				start_date: start_date,
				end_date: end_date,
				method_id: methodId,
				city: city,
				country: country,
			},
			success: function ( response ) {
				if ( response.success && response.data.length > 0 ) {
					jpLoadedTimings = response.data;

					// Default to current month if we are viewing the current year, otherwise January
					const now = new Date();
					const currentYearStr = String( now.getFullYear() );
					if ( year === currentYearStr ) {
						jpActiveMonth = String( now.getMonth() + 1 ).padStart(
							2,
							'0'
						);
					} else {
						jpActiveMonth = '01';
					}

					renderMonthPage( jpActiveMonth );
					renderPagination();
				} else {
					tableBody.html(
						`<tr><td colspan="14" class="text-center">${
							i18n.noMethodData ||
							'No database timings found for this calculation method. Please go to Settings & Sync tab, select this method, click Save Settings, and run the Year Synchronization first.'
						}</td></tr>`
					);
				}
			},
			error: function ( xhr ) {
				console.error( xhr );
				tableBody.html(
					`<tr><td colspan="13" class="text-center error">${
						i18n.failedLoad ||
						'Failed to load timings database rows.'
					}</td></tr>`
				);
			},
		} );
	}

	function renderMonthPage( monthStr ) {
		const monthStrNormalized = String( monthStr );
		const filteredRows = jpLoadedTimings.filter( function ( row ) {
			return row.prayer_date.substring( 5, 7 ) === monthStrNormalized;
		} );

		const tableBody = $( '#jetprayer-table-body' );
		if ( filteredRows.length > 0 ) {
			renderTimingsTable( filteredRows );
		} else {
			const monthName = i18n.months[ monthStr ] || monthStr;
			const fallbackMsg = i18n.noDataMonth
				? sprintf( i18n.noDataMonth, monthName )
				: `No data loaded for ${ monthName }.`;
			tableBody.html(
				`<tr><td colspan="14" class="text-center">${ fallbackMsg }</td></tr>`
			);
		}
	}

	function renderPagination() {
		const paginationContainer = $( '#jp-pagination-container' );
		paginationContainer.empty().removeClass( 'hidden' );

		const nav = $(
			'<nav aria-label="JetPrayer Timetable Pagination" class="jp-pagination-nav-inner"></nav>'
		);
		const ul = $( '<ul class="jp-pagination-list"></ul>' );

		// Prev Button
		const prevLi = $( '<li class="jp-pagination-item"></li>' );
		const prevLink = $(
			'<button type="button" class="jp-pagination-button jp-pagination-prev"></button>'
		)
			.html(
				'<span class="dashicons dashicons-arrow-left-alt2"></span> ' +
					( i18n.prev || 'Prev' )
			)
			.prop( 'disabled', jpActiveMonth === '01' )
			.on( 'click', function ( e ) {
				e.preventDefault();
				const currentInt = parseInt( jpActiveMonth, 10 );
				if ( currentInt > 1 ) {
					jpActiveMonth = String( currentInt - 1 ).padStart( 2, '0' );
					renderMonthPage( jpActiveMonth );
					renderPagination();
				}
			} );
		prevLi.append( prevLink );
		ul.append( prevLi );

		// Month Pages (1 to 12)
		for ( let m = 1; m <= 12; m++ ) {
			const mStr = String( m ).padStart( 2, '0' );
			const monthLabel = i18n.months[ mStr ]
				? i18n.months[ mStr ].substring( 0, 3 )
				: mStr; // Short name, e.g. Jan, Feb

			const li = $( '<li class="jp-pagination-item"></li>' );
			const button = $(
				'<button type="button" class="jp-pagination-button"></button>'
			)
				.text( monthLabel )
				.attr( 'title', i18n.months[ mStr ] || '' )
				.attr( 'data-month', mStr );

			if ( mStr === jpActiveMonth ) {
				button.addClass( 'jp-active' ).attr( 'aria-current', 'page' );
			}

			button.on( 'click', function ( e ) {
				e.preventDefault();
				jpActiveMonth = String( $( this ).attr( 'data-month' ) );
				renderMonthPage( jpActiveMonth );
				renderPagination();
			} );

			li.append( button );
			ul.append( li );
		}

		// Next Button
		const nextLi = $( '<li class="jp-pagination-item"></li>' );
		const nextLink = $(
			'<button type="button" class="jp-pagination-button jp-pagination-next"></button>'
		)
			.html(
				( i18n.next || 'Next' ) +
					' <span class="dashicons dashicons-arrow-right-alt2"></span>'
			)
			.prop( 'disabled', jpActiveMonth === '12' )
			.on( 'click', function ( e ) {
				e.preventDefault();
				const currentInt = parseInt( jpActiveMonth, 10 );
				if ( currentInt < 12 ) {
					jpActiveMonth = String( currentInt + 1 ).padStart( 2, '0' );
					renderMonthPage( jpActiveMonth );
					renderPagination();
				}
			} );
		nextLi.append( nextLink );
		ul.append( nextLi );

		nav.append( ul );
		paginationContainer.append( nav );
	}

	function renderTimingsTable( rows ) {
		const tableBody = $( '#jetprayer-table-body' );
		tableBody.empty();

		$( '#jp-cb-select-all' ).prop( 'checked', false );
		$( '#jp-delete-selected-btn' ).addClass( 'hidden' );

		rows.forEach( function ( row ) {
			const statusBadge =
				parseInt( row.is_custom ) === 1
					? '<span class="jp-badge jp-badge-custom">Custom</span>'
					: '<span class="jp-badge jp-badge-synced">Synced</span>';

			const tr = $( '<tr data-date="' + row.prayer_date + '"></tr>' );
			tr.append(
				`<td class="jp-col-cb"><input type="checkbox" class="jp-row-cb" data-date="${ row.prayer_date }"></td>`
			);
			tr.append(
				`<td class="jp-col-date font-mono">${ row.prayer_date }</td>`
			);
			tr.append( `<td class="jp-val-fajr font-mono">${ row.fajr }</td>` );
			tr.append(
				`<td class="jp-val-sunrise font-mono">${ row.sunrise }</td>`
			);
			tr.append(
				`<td class="jp-val-dhuhr font-mono">${ row.dhuhr }</td>`
			);
			tr.append( `<td class="jp-val-asr font-mono">${ row.asr }</td>` );
			tr.append(
				`<td class="jp-val-sunset font-mono">${ row.sunset }</td>`
			);
			tr.append(
				`<td class="jp-val-maghrib font-mono">${ row.maghrib }</td>`
			);
			tr.append( `<td class="jp-val-isha font-mono">${ row.isha }</td>` );
			tr.append(
				`<td class="jp-val-imsak font-mono">${ row.imsak }</td>`
			);
			tr.append(
				`<td class="jp-val-midnight font-mono">${ row.midnight }</td>`
			);
			tr.append( `<td class="jp-val-hijri">${ row.hijri_date }</td>` );
			tr.append( `<td class="jp-status">${ statusBadge }</td>` );
			tr.append( `<td class="jp-col-actions">
				<button type="button" class="button button-small jp-edit-btn">Edit</button>
			</td>` );

			tableBody.append( tr );
		} );
	}

	// CRUD Table Inline Actions
	const cacheOriginals = {};

	// Click Edit
	$( document ).on( 'click', '.jp-edit-btn', function () {
		const tr = $( this ).closest( 'tr' );
		const date = tr.data( 'date' );

		// Extract current text values
		const rowData = {
			fajr: tr.find( '.jp-val-fajr' ).text(),
			sunrise: tr.find( '.jp-val-sunrise' ).text(),
			dhuhr: tr.find( '.jp-val-dhuhr' ).text(),
			asr: tr.find( '.jp-val-asr' ).text(),
			sunset: tr.find( '.jp-val-sunset' ).text(),
			maghrib: tr.find( '.jp-val-maghrib' ).text(),
			isha: tr.find( '.jp-val-isha' ).text(),
			imsak: tr.find( '.jp-val-imsak' ).text(),
			midnight: tr.find( '.jp-val-midnight' ).text(),
			hijri: tr.find( '.jp-val-hijri' ).text(),
		};

		// Save to temporary cache in case user cancels edit
		cacheOriginals[ date ] = rowData;

		// Convert columns into inputs (excluding date, status, actions)
		tr.find( '.jp-val-fajr' ).html(
			`<input type="text" value="${ rowData.fajr }" class="jp-inline-input">`
		);
		tr.find( '.jp-val-sunrise' ).html(
			`<input type="text" value="${ rowData.sunrise }" class="jp-inline-input">`
		);
		tr.find( '.jp-val-dhuhr' ).html(
			`<input type="text" value="${ rowData.dhuhr }" class="jp-inline-input">`
		);
		tr.find( '.jp-val-asr' ).html(
			`<input type="text" value="${ rowData.asr }" class="jp-inline-input">`
		);
		tr.find( '.jp-val-sunset' ).html(
			`<input type="text" value="${ rowData.sunset }" class="jp-inline-input">`
		);
		tr.find( '.jp-val-maghrib' ).html(
			`<input type="text" value="${ rowData.maghrib }" class="jp-inline-input">`
		);
		tr.find( '.jp-val-isha' ).html(
			`<input type="text" value="${ rowData.isha }" class="jp-inline-input">`
		);
		tr.find( '.jp-val-imsak' ).html(
			`<input type="text" value="${ rowData.imsak }" class="jp-inline-input">`
		);
		tr.find( '.jp-val-midnight' ).html(
			`<input type="text" value="${ rowData.midnight }" class="jp-inline-input">`
		);
		tr.find( '.jp-val-hijri' ).html(
			`<input type="text" value="${ rowData.hijri }" class="jp-inline-input wide">`
		);

		// Adjust action button row
		tr.find( '.jp-col-actions' ).html( `
			<button type="button" class="button button-small button-primary jp-save-btn">Save</button>
			<button type="button" class="button button-small jp-cancel-btn">Cancel</button>
		` );
	} );

	// Click Cancel
	$( document ).on( 'click', '.jp-cancel-btn', function () {
		const tr = $( this ).closest( 'tr' );
		const date = tr.data( 'date' );
		const original = cacheOriginals[ date ];

		if ( original ) {
			tr.find( '.jp-val-fajr' ).text( original.fajr );
			tr.find( '.jp-val-sunrise' ).text( original.sunrise );
			tr.find( '.jp-val-dhuhr' ).text( original.dhuhr );
			tr.find( '.jp-val-asr' ).text( original.asr );
			tr.find( '.jp-val-sunset' ).text( original.sunset );
			tr.find( '.jp-val-maghrib' ).text( original.maghrib );
			tr.find( '.jp-val-isha' ).text( original.isha );
			tr.find( '.jp-val-imsak' ).text( original.imsak );
			tr.find( '.jp-val-midnight' ).text( original.midnight );
			tr.find( '.jp-val-hijri' ).text( original.hijri );

			tr.find( '.jp-col-actions' ).html( `
				<button type="button" class="button button-small jp-edit-btn">Edit</button>
			` );

			delete cacheOriginals[ date ];
		}
	} );

	// Click Save
	$( document ).on( 'click', '.jp-save-btn', function () {
		const tr = $( this ).closest( 'tr' );
		const date = tr.data( 'date' );

		const postData = {
			prayer_date: date,
			method_id: $( '#jp_edit_method' ).val(),
			city: $( '#jp_edit_city' ).val(),
			country: $( '#jp_edit_country' ).val(),
			fajr: tr.find( '.jp-val-fajr input' ).val(),
			sunrise: tr.find( '.jp-val-sunrise input' ).val(),
			dhuhr: tr.find( '.jp-val-dhuhr input' ).val(),
			asr: tr.find( '.jp-val-asr input' ).val(),
			sunset: tr.find( '.jp-val-sunset input' ).val(),
			maghrib: tr.find( '.jp-val-maghrib input' ).val(),
			isha: tr.find( '.jp-val-isha input' ).val(),
			imsak: tr.find( '.jp-val-imsak input' ).val(),
			midnight: tr.find( '.jp-val-midnight input' ).val(),
			hijri_date: tr.find( '.jp-val-hijri input' ).val(),
		};

		// Disable action buttons during save
		tr.find( '.jp-col-actions button' ).prop( 'disabled', true );

		$.ajax( {
			url: restUrl + '/update',
			method: 'POST',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', restNonce );
			},
			data: postData,
			success: function ( response ) {
				// Save text value in columns
				tr.find( '.jp-val-fajr' ).text( postData.fajr );
				tr.find( '.jp-val-sunrise' ).text( postData.sunrise );
				tr.find( '.jp-val-dhuhr' ).text( postData.dhuhr );
				tr.find( '.jp-val-asr' ).text( postData.asr );
				tr.find( '.jp-val-sunset' ).text( postData.sunset );
				tr.find( '.jp-val-maghrib' ).text( postData.maghrib );
				tr.find( '.jp-val-isha' ).text( postData.isha );
				tr.find( '.jp-val-imsak' ).text( postData.imsak );
				tr.find( '.jp-val-midnight' ).text( postData.midnight );
				tr.find( '.jp-val-hijri' ).text( postData.hijri_date );

				// Update status column badge to custom
				tr.find( '.jp-status' ).html(
					'<span class="jp-badge jp-badge-custom">Custom</span>'
				);

				tr.find( '.jp-col-actions' ).html( `
					<button type="button" class="button button-small jp-edit-btn">Edit</button>
				` );

				// Update memory cache if in year mode
				if ( jpCurrentMode === 'year' && jpLoadedTimings.length > 0 ) {
					const selectedMethod = $( '#jp_edit_method' ).val();
					const match = jpLoadedTimings.find( function ( item ) {
						return (
							item.prayer_date === date &&
							parseInt( item.method_id ) ===
								parseInt( selectedMethod )
						);
					} );
					if ( match ) {
						match.fajr = postData.fajr;
						match.sunrise = postData.sunrise;
						match.dhuhr = postData.dhuhr;
						match.asr = postData.asr;
						match.sunset = postData.sunset;
						match.maghrib = postData.maghrib;
						match.isha = postData.isha;
						match.imsak = postData.imsak;
						match.midnight = postData.midnight;
						match.hijri_date = postData.hijri_date;
						match.is_custom = 1;
					}
				}

				delete cacheOriginals[ date ];
			},
			error: function ( xhr ) {
				console.error( xhr );
				alert( i18n.error );
				// Re-enable action buttons
				tr.find( '.jp-col-actions button' ).prop( 'disabled', false );
			},
		} );
	} );

	// ==========================================
	// CRUD TABLE MULTI-SELECT & BULK DELETE
	// ==========================================

	$( document ).on( 'change', '#jp-cb-select-all', function () {
		const isChecked = $( this ).prop( 'checked' );
		$( '.jp-row-cb' ).prop( 'checked', isChecked );
		toggleDeleteButton();
	} );

	$( document ).on( 'change', '.jp-row-cb', function () {
		const allChecked =
			$( '.jp-row-cb' ).length === $( '.jp-row-cb:checked' ).length;
		$( '#jp-cb-select-all' ).prop( 'checked', allChecked );
		toggleDeleteButton();
	} );

	function toggleDeleteButton() {
		const checkedCount = $( '.jp-row-cb:checked' ).length;
		if ( checkedCount > 0 ) {
			$( '#jp-delete-selected-btn' ).removeClass( 'hidden' );
		} else {
			$( '#jp-delete-selected-btn' ).addClass( 'hidden' );
		}
	}

	// Close Delete Modal
	$( document ).on( 'click', '.jp-delete-modal-close', function ( e ) {
		e.preventDefault();
		$( '#jp-delete-confirm-modal' ).addClass( 'hidden' );
	} );

	let activeDatesToDelete = [];

	$( document ).on( 'click', '#jp-delete-selected-btn', function ( e ) {
		e.preventDefault();

		const checkedCBs = $( '.jp-row-cb:checked' );
		activeDatesToDelete = [];
		checkedCBs.each( function () {
			const date = $( this ).data( 'date' );
			if ( date ) {
				activeDatesToDelete.push( date );
			}
		} );

		if ( activeDatesToDelete.length === 0 ) {
			return;
		}

		const year = $( '#jp_edit_year' ).val();
		const methodId = $( '#jp_edit_method' ).val();
		const country = $( '#jp_edit_country' ).val();
		const city = $( '#jp_edit_city' ).val();

		if ( jpCurrentMode === 'year' ) {
			const monthName =
				( i18n && i18n.months && i18n.months[ jpActiveMonth ] ) ||
				jpActiveMonth;
			const descSelected = i18n.deleteSelectedYearDesc
				? sprintf(
						i18n.deleteSelectedYearDesc,
						activeDatesToDelete.length,
						monthName,
						year
				  )
				: `${ monthName } ${ year } içindeki seçilen ${ activeDatesToDelete.length } günü siler.`;
			const descYear = i18n.deleteYearDesc
				? sprintf( i18n.deleteYearDesc, year, city, country )
				: `${ city }, ${ country } için ${ year } yılına ait tüm verileri (365 günü) temizler.`;
			$( '#jp-delete-selected-desc' ).text( descSelected );
			$( '#jp-delete-year-desc' ).text( descYear );
			$( '#jp-delete-confirm-modal' ).removeClass( 'hidden' );
		} else {
			const confirmMsg = i18n.confirmDeleteSelected
				? sprintf(
						i18n.confirmDeleteSelected,
						activeDatesToDelete.length
				  )
				: 'Are you sure you want to delete these ' +
				  activeDatesToDelete.length +
				  ' timing records?';
			if ( ! confirm( confirmMsg ) ) {
				return;
			}
			executeTimingsDelete(
				activeDatesToDelete,
				false,
				year,
				methodId,
				city,
				country
			);
		}
	} );

	$( document ).on( 'click', '#jp-confirm-delete-action-btn', function ( e ) {
		e.preventDefault();

		const deleteType = $( 'input[name="jp-delete-type"]:checked' ).val();
		const year = $( '#jp_edit_year' ).val();
		const methodId = $( '#jp_edit_method' ).val();
		const country = $( '#jp_edit_country' ).val();
		const city = $( '#jp_edit_city' ).val();

		if ( deleteType === 'year' ) {
			const confirmMsg = i18n.confirmDeleteYear
				? sprintf( i18n.confirmDeleteYear, year, city, country )
				: 'WARNING: Are you sure you want to delete the ENTIRE year ' +
				  year +
				  ' of timings for ' +
				  city +
				  ', ' +
				  country +
				  '?';
			if ( ! confirm( confirmMsg ) ) {
				return;
			}
			executeTimingsDelete( [], true, year, methodId, city, country );
		} else {
			executeTimingsDelete(
				activeDatesToDelete,
				false,
				year,
				methodId,
				city,
				country
			);
		}
	} );

	function executeTimingsDelete(
		dates,
		deleteAllYear,
		year,
		methodId,
		city,
		country
	) {
		const deleteBtn = $( '#jp-delete-selected-btn' );
		const confirmBtn = $( '#jp-confirm-delete-action-btn' );

		deleteBtn.prop( 'disabled', true ).addClass( 'loading' );
		confirmBtn.prop( 'disabled', true ).addClass( 'loading' );

		$.ajax( {
			url: restUrl + '/delete',
			method: 'POST',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', restNonce );
			},
			data: {
				dates: dates,
				delete_all_year: deleteAllYear ? 1 : 0,
				year: year,
				method_id: methodId,
				city: city,
				country: country,
			},
			success: function ( response ) {
				$( '#jp-delete-confirm-modal' ).addClass( 'hidden' );

				if ( deleteAllYear ) {
					if ( jpCurrentMode === 'year' ) {
						jpLoadedTimings = [];
					}
					$( '#jetprayer-table-body' ).empty();
				} else {
					if (
						jpCurrentMode === 'year' &&
						Array.isArray( jpLoadedTimings ) &&
						jpLoadedTimings.length > 0
					) {
						jpLoadedTimings = jpLoadedTimings.filter(
							function ( item ) {
								if (
									typeof item === 'object' &&
									item !== null &&
									item.prayer_date
								) {
									return ! dates.includes( item.prayer_date );
								}
								return true;
							}
						);
					}
					dates.forEach( function ( d ) {
						$( `tr[data-date="${ d }"]` ).remove();
					} );
				}

				$( '#jp-cb-select-all' ).prop( 'checked', false );
				toggleDeleteButton();

				const tableBody = $( '#jetprayer-table-body' );
				if ( tableBody.children( 'tr' ).length === 0 ) {
					if ( jpCurrentMode === 'year' ) {
						const monthName =
							( i18n &&
								i18n.months &&
								i18n.months[ jpActiveMonth ] ) ||
							jpActiveMonth;
						const fallbackMsg = i18n.noDataMonth
							? sprintf( i18n.noDataMonth, monthName )
							: `No data loaded for ${ monthName }.`;
						tableBody.html(
							`<tr><td colspan="14" class="text-center">${ fallbackMsg }</td></tr>`
						);
					} else {
						tableBody.html(
							`<tr><td colspan="14" class="text-center">${
								( i18n && i18n.noMethodData ) ||
								'No database timings found for this calculation method.'
							}</td></tr>`
						);
					}
				}

				if ( typeof loadSyncedFilters === 'function' ) {
					loadSyncedFilters( country, city, methodId );
				}
				if ( typeof refreshSyncedMethods === 'function' ) {
					refreshSyncedMethods();
				}

				alert(
					response.message ||
						i18n.deleteSuccess ||
						'Timings deleted successfully!'
				);
			},
			error: function ( xhr ) {
				console.error( xhr );
				let errMsg = i18n.deleteFailed || 'Failed to delete timings.';
				if ( xhr.responseJSON && xhr.responseJSON.message ) {
					errMsg = xhr.responseJSON.message;
				}
				alert( errMsg );
			},
			complete: function () {
				deleteBtn.prop( 'disabled', false ).removeClass( 'loading' );
				confirmBtn.prop( 'disabled', false ).removeClass( 'loading' );
			},
		} );
	}

	// ==========================================
	// BULK SYNC IMPLEMENTATION
	// ==========================================
	let bulkSyncActive = false;
	let bulkSyncQueue = [];
	let bulkSyncTotal = 0;
	let bulkSyncSuccessCount = 0;
	let bulkSyncErrorCount = 0;

	// Modal Open
	$( document ).on( 'click', '#jp-bulk-sync-trigger', function ( e ) {
		e.preventDefault();
		$( '#jp-bulk-modal' ).removeClass( 'hidden' );
		$( '#jp-bulk-phase-upload' ).removeClass( 'hidden' );
		$( '#jp-bulk-phase-progress' ).addClass( 'hidden' );
	} );

	// Modal Close
	$( document ).on(
		'click',
		'.jp-modal-close, #jp-bulk-done-btn',
		function ( e ) {
			e.preventDefault();
			$( '#jp-bulk-modal' ).addClass( 'hidden' );
			bulkSyncActive = false;
		}
	);

	// Cancel Button
	$( document ).on( 'click', '#jp-bulk-cancel-btn', function ( e ) {
		e.preventDefault();
		bulkSyncActive = false;
		$( this ).addClass( 'hidden' );
	} );

	// Download JSON Template Dynamically
	$( document ).on( 'click', '#jp-bulk-download-template', function ( e ) {
		e.preventDefault();
		const templateData = {
			Turkey: [ 'Istanbul', '13', '2026', 'Ankara', 'Izmir' ],
			Lebanon: [ 'Beirut', 'Tripoli' ],
			Yemen: [ 'Sanaa', '7', '2026', 'Aden', 'Taiz', '4', '2026' ],
		};
		const dataStr =
			'data:text/json;charset=utf-8,' +
			encodeURIComponent( JSON.stringify( templateData, null, 2 ) );
		const downloadAnchor = document.createElement( 'a' );
		downloadAnchor.setAttribute( 'href', dataStr );
		downloadAnchor.setAttribute(
			'download',
			'jetprayer_bulk_sync_template.json'
		);
		downloadAnchor.click();
	} );

	// File Upload Handler
	const fileInput = $( '#jp-bulk-file-input' );
	fileInput.on( 'change', function ( e ) {
		const file = e.target.files[ 0 ];
		if ( ! file ) {
			return;
		}

		const reader = new FileReader();
		reader.onload = function ( evt ) {
			try {
				const parsed = JSON.parse( evt.target.result );
				if (
					typeof parsed !== 'object' ||
					parsed === null ||
					Array.isArray( parsed )
				) {
					alert(
						i18n.invalidJson ||
							'Invalid JSON structure. Root must be a JSON object.'
					);
					return;
				}

				const queue = parseBulkSyncJson( parsed );
				if ( queue.length === 0 ) {
					alert(
						i18n.noLocationsJson ||
							'No valid locations found in the JSON file.'
					);
					return;
				}

				startBulkSync( queue );
			} catch ( err ) {
				const parseErrMsg = i18n.failedParseJson
					? sprintf( i18n.failedParseJson, err.message )
					: 'Failed to parse JSON file: ' + err.message;
				alert( parseErrMsg );
			}
		};
		reader.readAsText( file );
		fileInput.val( '' ); // Reset input
	} );

	// Custom Parser for Country array parsing format
	function parseBulkSyncJson( data ) {
		const queue = [];
		const defaultMethod = $( '#jp_method' ).val() || '13';
		const defaultYear =
			$( '#jp_sync_year' ).val() || String( new Date().getFullYear() );

		function isYear( str ) {
			return /^\d{4}$/.test( str );
		}

		function isMethod( str ) {
			return /^\d{1,2}$/.test( str ) && parseInt( str, 10 ) <= 30;
		}

		for ( const country in data ) {
			if ( ! data.hasOwnProperty( country ) ) {
				continue;
			}
			const list = data[ country ];
			if ( ! Array.isArray( list ) ) {
				continue;
			}

			let pendingCities = [];
			let currentMethod = null;
			let currentYear = null;

			for ( let i = 0; i < list.length; i++ ) {
				const val = String( list[ i ] ).trim();
				if ( isYear( val ) ) {
					currentYear = val;
				} else if ( isMethod( val ) ) {
					currentMethod = val;
				} else {
					if (
						pendingCities.length > 0 &&
						( currentMethod !== null || currentYear !== null )
					) {
						pendingCities.forEach( function ( city ) {
							queue.push( {
								city: city,
								country: country,
								method: currentMethod || defaultMethod,
								year: currentYear || defaultYear,
							} );
						} );
						pendingCities = [];
						currentMethod = null;
						currentYear = null;
					}
					pendingCities.push( val );
				}
			}
			// Commit remaining
			if ( pendingCities.length > 0 ) {
				pendingCities.forEach( function ( city ) {
					queue.push( {
						city: city,
						country: country,
						method: currentMethod || defaultMethod,
						year: currentYear || defaultYear,
					} );
				} );
			}
		}
		return queue;
	}

	function startBulkSync( queue ) {
		bulkSyncQueue = queue;
		bulkSyncTotal = queue.length;
		bulkSyncSuccessCount = 0;
		bulkSyncErrorCount = 0;
		bulkSyncActive = true;

		$( '#jp-bulk-phase-upload' ).addClass( 'hidden' );
		$( '#jp-bulk-phase-progress' ).removeClass( 'hidden' );
		$( '#jp-bulk-cancel-btn' ).removeClass( 'hidden' );
		$( '#jp-bulk-done-btn' ).addClass( 'hidden' );

		updateProgressUI();
		const bulkStartMsg = i18n.bulkSyncStarted
			? sprintf( i18n.bulkSyncStarted, bulkSyncTotal )
			: 'Bulk sync started. Total locations: ' + bulkSyncTotal;
		$( '#jp-bulk-progress-log' ).html(
			`<p class="info">${ bulkStartMsg }</p>`
		);

		processNextBulkItem();
	}

	function updateProgressUI() {
		const processed = bulkSyncSuccessCount + bulkSyncErrorCount;
		const pct =
			bulkSyncTotal > 0
				? Math.round( ( processed / bulkSyncTotal ) * 100 )
				: 0;
		$( '#jp-bulk-progress-bar' ).css( 'width', pct + '%' );
		$( '#jp-bulk-progress-text' ).text( processed + ' / ' + bulkSyncTotal );
		$( '#jp-bulk-progress-pct' ).text( pct + '%' );
	}

	function processNextBulkItem() {
		const logBox = $( '#jp-bulk-progress-log' );

		if ( ! bulkSyncActive ) {
			logBox.append(
				`<p class="error">${
					i18n.bulkSyncCancelled || 'Bulk sync cancelled by user.'
				}</p>`
			);
			$( '#jp-bulk-cancel-btn' ).addClass( 'hidden' );
			$( '#jp-bulk-done-btn' ).removeClass( 'hidden' );
			if ( bulkSyncSuccessCount > 0 ) {
				const now = new Date();
				const formattedTime =
					now.getFullYear() +
					'-' +
					String( now.getMonth() + 1 ).padStart( 2, '0' ) +
					'-' +
					String( now.getDate() ).padStart( 2, '0' ) +
					' ' +
					String( now.getHours() ).padStart( 2, '0' ) +
					':' +
					String( now.getMinutes() ).padStart( 2, '0' ) +
					':' +
					String( now.getSeconds() ).padStart( 2, '0' );
				$( '#jetprayer-sync-time' ).text( formattedTime );
				$( '.status-indicator' )
					.removeClass( 'pending' )
					.addClass( 'synced' );
				if ( typeof refreshSyncedMethods === 'function' ) {
					refreshSyncedMethods();
				}
				loadSyncedFilters();
			}
			return;
		}

		if ( bulkSyncQueue.length === 0 ) {
			const completeMsg = i18n.bulkSyncComplete
				? sprintf(
						i18n.bulkSyncComplete,
						bulkSyncSuccessCount,
						bulkSyncErrorCount
				  )
				: 'Bulk sync completed! Success: ' +
				  bulkSyncSuccessCount +
				  ', Failed: ' +
				  bulkSyncErrorCount;
			logBox.append( `<p class="success"><b>${ completeMsg }</b></p>` );
			$( '#jp-bulk-cancel-btn' ).addClass( 'hidden' );
			$( '#jp-bulk-done-btn' ).removeClass( 'hidden' );
			bulkSyncActive = false;

			if ( bulkSyncSuccessCount > 0 ) {
				const now = new Date();
				const formattedTime =
					now.getFullYear() +
					'-' +
					String( now.getMonth() + 1 ).padStart( 2, '0' ) +
					'-' +
					String( now.getDate() ).padStart( 2, '0' ) +
					' ' +
					String( now.getHours() ).padStart( 2, '0' ) +
					':' +
					String( now.getMinutes() ).padStart( 2, '0' ) +
					':' +
					String( now.getSeconds() ).padStart( 2, '0' );
				$( '#jetprayer-sync-time' ).text( formattedTime );
				$( '.status-indicator' )
					.removeClass( 'pending' )
					.addClass( 'synced' );
			}

			if ( typeof refreshSyncedMethods === 'function' ) {
				refreshSyncedMethods();
			}
			loadSyncedFilters();
			return;
		}

		const item = bulkSyncQueue.shift();
		const syncMsg = i18n.syncingLocation
			? sprintf(
					i18n.syncingLocation,
					item.city,
					item.country,
					item.method,
					item.year
			  )
			: 'Syncing ' +
			  item.city +
			  ', ' +
			  item.country +
			  ' (Method: ' +
			  item.method +
			  ', Year: ' +
			  item.year +
			  ')...';
		logBox.append( `<p class="info">${ syncMsg }</p>` );
		logBox.scrollTop( logBox[ 0 ].scrollHeight );

		$.ajax( {
			url: restUrl + '/sync',
			method: 'POST',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', restNonce );
			},
			data: {
				year: item.year,
				type: 'city',
				city: item.city,
				country: item.country,
				method: item.method,
				school: $( '#jp_school' ).val() || '0',
				timezone: '',
				bulk_sync: 1, // Bypass rate limit lock
			},
			success: function ( response ) {
				bulkSyncSuccessCount++;
				logBox.append(
					'<p class="success">✓ ' +
						item.city +
						', ' +
						item.country +
						': ' +
						( response.message ||
							i18n.syncedSuccess ||
							'Synced successfully.' ) +
						'</p>'
				);
			},
			error: function ( xhr ) {
				bulkSyncErrorCount++;
				let errMsg = i18n.apiError || 'API Error';
				if ( xhr.responseJSON && xhr.responseJSON.message ) {
					errMsg = xhr.responseJSON.message;
				}
				logBox.append(
					'<p class="error">✗ ' +
						item.city +
						', ' +
						item.country +
						': ' +
						errMsg +
						'</p>'
				);
			},
			complete: function () {
				updateProgressUI();
				logBox.scrollTop( logBox[ 0 ].scrollHeight );
				// Safe delay to prevent AlAdhan API lock
				setTimeout( processNextBulkItem, 400 );
			},
		} );
	}

	// ==========================================
	// EDITOR DYNAMIC FILTERS (SYNCED LOCATIONS ONLY)
	// ==========================================
	let syncedLocationsData = [];

	function loadSyncedFilters( selectCountry, selectCity, selectMethod ) {
		const countrySelect = $( '#jp_edit_country' );
		const citySelect = $( '#jp_edit_city' );
		const methodSelect = $( '#jp_edit_method' );

		countrySelect.html(
			`<option value="">${
				i18n.loadingCountries || 'Loading countries...'
			}</option>`
		);
		citySelect.html(
			`<option value="">${
				i18n.loadingCities || 'Loading cities...'
			}</option>`
		);
		methodSelect.html(
			`<option value="">${
				i18n.loadingMethods || 'Loading methods...'
			}</option>`
		);

		$.ajax( {
			url: restUrl + '/synced-locations',
			method: 'GET',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', restNonce );
			},
			success: function ( response ) {
				syncedLocationsData = ( response && response.data ) || [];
				if ( syncedLocationsData.length === 0 ) {
					countrySelect.html(
						`<option value="">${
							i18n.noSyncedCountries || 'No synced countries'
						}</option>`
					);
					citySelect.html(
						`<option value="">${
							i18n.noSyncedCities || 'No synced cities'
						}</option>`
					);
					methodSelect.html(
						`<option value="">${
							i18n.noSyncedMethods || 'No synced methods'
						}</option>`
					);
					return;
				}

				populateEditCountries(
					selectCountry,
					selectCity,
					selectMethod
				);
			},
			error: function () {
				countrySelect.html(
					`<option value="">${
						i18n.errorCountries || 'Error loading countries'
					}</option>`
				);
				citySelect.html(
					`<option value="">${
						i18n.errorCities || 'Error loading cities'
					}</option>`
				);
				methodSelect.html(
					`<option value="">${
						i18n.errorMethods || 'Error loading methods'
					}</option>`
				);
			},
		} );
	}

	function populateEditCountries( selectCountry, selectCity, selectMethod ) {
		const countrySelect = $( '#jp_edit_country' );
		const countries = [];

		syncedLocationsData.forEach( function ( item ) {
			if ( item.country && ! countries.includes( item.country ) ) {
				countries.push( item.country );
			}
		} );

		countries.sort();

		countrySelect.empty();
		countries.forEach( function ( c ) {
			countrySelect.append(
				'<option value="' + c + '">' + c + '</option>'
			);
		} );

		if ( selectCountry && countries.includes( selectCountry ) ) {
			countrySelect.val( selectCountry );
		}

		populateEditCities( selectCity, selectMethod );
	}

	function populateEditCities( selectCity, selectMethod ) {
		const country = $( '#jp_edit_country' ).val();
		const citySelect = $( '#jp_edit_city' );
		const cities = [];

		if ( ! country ) {
			citySelect
				.empty()
				.append(
					`<option value="">${
						i18n.selectCountryFirst || 'Select country first'
					}</option>`
				);
			return;
		}

		syncedLocationsData.forEach( function ( item ) {
			if (
				item.country === country &&
				item.city &&
				! cities.includes( item.city )
			) {
				cities.push( item.city );
			}
		} );

		cities.sort();

		citySelect.empty();
		cities.forEach( function ( c ) {
			citySelect.append( '<option value="' + c + '">' + c + '</option>' );
		} );

		if ( selectCity && cities.includes( selectCity ) ) {
			citySelect.val( selectCity );
		}

		populateEditMethods( selectMethod );
	}

	function populateEditMethods( selectMethod ) {
		const country = $( '#jp_edit_country' ).val();
		const city = $( '#jp_edit_city' ).val();
		const methodSelect = $( '#jp_edit_method' );
		const methodsList = [];

		if ( ! country || ! city ) {
			methodSelect
				.empty()
				.append(
					`<option value="">${
						i18n.selectCityFirst || 'Select city first'
					}</option>`
				);
			return;
		}

		const methodNames =
			jetprayerAdmin && jetprayerAdmin.methods
				? jetprayerAdmin.methods
				: {};

		syncedLocationsData.forEach( function ( item ) {
			if ( item.country === country && item.city === city ) {
				const mId = parseInt( item.method_id, 10 );
				if ( ! methodsList.includes( mId ) ) {
					methodsList.push( mId );
				}
			}
		} );

		methodsList.sort( function ( a, b ) {
			return a - b;
		} );

		methodSelect.empty();
		methodsList.forEach( function ( mId ) {
			const fallbackName = i18n.methodLabel
				? sprintf( i18n.methodLabel, mId )
				: 'Method ' + mId;
			const mName = methodNames[ mId ] || fallbackName;
			methodSelect.append(
				'<option value="' +
					mId +
					'">' +
					mId +
					' — ' +
					mName +
					'</option>'
			);
		} );

		if (
			selectMethod &&
			methodsList.includes( parseInt( selectMethod, 10 ) )
		) {
			methodSelect.val( selectMethod );
		}
	}

	// Change Listeners for editor dynamic select cascades
	$( '#jp_edit_country' ).on( 'change', function () {
		populateEditCities();
	} );

	$( '#jp_edit_city' ).on( 'change', function () {
		populateEditMethods();
	} );
} );

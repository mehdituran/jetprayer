import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { SelectControl, TextControl, PanelBody } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import metadata from './block.json';

// Localized by jetprayer_localize_block_editor_methods() in jetprayer.php,
// keeping this list in sync with JetPrayer_API::get_calculation_methods()
// so the editor never has to hardcode the AlAdhan method IDs.
const methodOptions = [
	{ label: __( 'Use Default (Settings & Sync)', 'jetprayer' ), value: '' },
	{ label: __( 'All Methods (No Constraint)', 'jetprayer' ), value: 'all' },
	...Object.entries( window.jetprayerMethods || {} ).map(
		( [ id, name ] ) => ( {
			label: `${ id } — ${ name }`,
			value: id,
		} )
	),
];

const countryOptions = [
	{ label: __( 'Use Default (Settings & Sync)', 'jetprayer' ), value: '' },
	...( window.jetprayerSyncedCountries || [] ).map( ( country ) => ( {
		label: country,
		value: country,
	} ) ),
];

const Edit = ( { attributes, setAttributes } ) => {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( 'JetPrayer Layout Settings', 'jetprayer' ) }
				>
					<SelectControl
						label={ __( 'Display Layout', 'jetprayer' ) }
						value={ attributes.layout }
						options={ [
							{
								label: __( 'Premium Card', 'jetprayer' ),
								value: 'card',
							},
							{
								label: __( 'Responsive Grid', 'jetprayer' ),
								value: 'grid',
							},
							{
								label: __( 'Interactive Slider', 'jetprayer' ),
								value: 'slider',
							},
							{
								label: __( 'Scrolling Ticker', 'jetprayer' ),
								value: 'ticker',
							},
							{
								label: __( 'Monthly Modal', 'jetprayer' ),
								value: 'modal',
							},
						] }
						onChange={ ( val ) => setAttributes( { layout: val } ) }
					/>
					<SelectControl
						label={ __( 'Calculation Method', 'jetprayer' ) }
						help={ __(
							'Override the default method for this block only. Requires that data was already synced for that Method ID.',
							'jetprayer'
						) }
						value={ attributes.method }
						options={ methodOptions }
						onChange={ ( val ) => setAttributes( { method: val } ) }
					/>
					<SelectControl
						label={ __( 'Country Selection', 'jetprayer' ) }
						help={ __(
							'Select the country from database synced options.',
							'jetprayer'
						) }
						value={ attributes.country }
						options={ countryOptions }
						onChange={ ( val ) =>
							setAttributes( { country: val } )
						}
					/>
					<TextControl
						label={ __( 'City Override(s)', 'jetprayer' ) }
						help={ __(
							'Optional. Enter a single city or comma-separated list of cities (e.g. Istanbul, Izmir). Leave blank to show all cities for the selected country.',
							'jetprayer'
						) }
						value={ attributes.city }
						onChange={ ( val ) => setAttributes( { city: val } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div
				style={ {
					background: '#0f172a',
					color: '#f8fafc',
					padding: '20px',
					borderRadius: '8px',
					border: '1px solid rgba(255,255,255,0.1)',
					textAlign: 'center',
					fontFamily: 'sans-serif',
				} }
			>
				<span
					className="dashicons dashicons-clock"
					style={ {
						fontSize: '32px',
						width: '32px',
						height: '32px',
						color: '#0ea5e9',
						display: 'block',
						margin: '0 auto 10px',
					} }
				></span>
				<h4 style={ { margin: '0 0 5px 0', fontSize: '15px' } }>
					JetPrayer - Islamic Prayer Times
				</h4>
				<p
					style={ {
						margin: '0',
						fontSize: '12px',
						opacity: 0.8,
					} }
				>
					{ __( 'Layout:', 'jetprayer' ) }{ ' ' }
					<strong>{ attributes.layout.toUpperCase() }</strong>
				</p>
				<p
					style={ {
						margin: '8px 0 0 0',
						fontSize: '11px',
						color: '#94a3b8',
					} }
				>
					{ __( '[Rendered Dynamically on Live Site]', 'jetprayer' ) }
				</p>
			</div>
		</div>
	);
};

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => {
		// Null means content is generated dynamically via PHP render_callback
		return null;
	},
} );

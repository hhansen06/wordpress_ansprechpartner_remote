const { registerBlockType } = wp.blocks;
const { useBlockProps, InspectorControls } = wp.blockEditor;
const { PanelBody, SelectControl, RadioControl } = wp.components;
const { useEffect, useState } = wp.element;
const { Fragment } = wp.element;
const apiFetch = wp.apiFetch;

registerBlockType('war/ansprechpartner', {
	edit: EditComponent,
});

function EditComponent({ attributes, setAttributes }) {
	const blockProps = useBlockProps();
	const [sparten, setSparten] = useState([]);
	const [funktionen, setFunktionen] = useState([]);
	const [loading, setLoading] = useState(true);

	const { displayMode, sparte, funktion } = attributes;

	// Sparten laden
	useEffect(() => {
		const loadSparten = async () => {
			try {
				// Versuche mit AJAX (zuverlässiger)
				const formData = new FormData();
				formData.append('action', 'war_get_sparten');

				const response = await fetch(warBlockData.ajaxUrl, {
					method: 'POST',
					body: formData,
				});

				const data = await response.json();
				
				const options = Array.isArray(data)
					? data.map(s => ({
						label: s.name || s.id,
						value: s.id || s.name
					}))
					: [];

				setSparten(options);
				setLoading(false);
			} catch (error) {
				console.error('Fehler beim Laden der Sparten:', error);
				setSparten([]);
				setLoading(false);
			}
		};

		loadSparten();
	}, []);

	// Funktionen laden wenn Sparte geändert wird
	useEffect(() => {
		if (!sparte) {
			setFunktionen([]);
			return;
		}

		const loadFunktionen = async () => {
			try {
				// Mit AJAX (zuverlässiger)
				const formData = new FormData();
				formData.append('action', 'war_get_ansprechpartner');
				formData.append('sparte', sparte);

				const response = await fetch(warBlockData.ajaxUrl, {
					method: 'POST',
					body: formData,
				});

				const data = await response.json();

				console.log('API Response Ansprechpartner für Sparte:', sparte, data);
				console.log('Anzahl Einträge:', data.length);

				const funcs = new Set();
				if (Array.isArray(data)) {
					data.forEach(person => {
						console.log('Person:', person.name, 'Funktion:', person.funktion);
						if (person.funktion) {
							funcs.add(person.funktion);
						}
					});
				}

				console.log('Gesammelte Funktionen:', Array.from(funcs));

				const options = Array.from(funcs).map(f => ({
					label: f,
					value: f
				}));

				setFunktionen(options);

				if (funktion && !funcs.has(funktion)) {
					setAttributes({ funktion: '' });
				}
			} catch (error) {
				console.error('Fehler beim Laden der Funktionen:', error);
				setFunktionen([]);
			}
		};

		loadFunktionen();
	}, [sparte, setAttributes, funktion]);

	return wp.element.createElement(
		Fragment,
		null,
		wp.element.createElement(
			InspectorControls,
			null,
			wp.element.createElement(
				PanelBody,
				{ title: 'Ansprechpartner Einstellungen' },
				wp.element.createElement(
					RadioControl,
					{
						label: 'Anzeigemodus',
						selected: displayMode || 'single',
						options: [
							{ label: 'Einzelne Karte', value: 'single' },
							{ label: 'Alle untereinander', value: 'all' }
						],
						onChange: (value) => setAttributes({ displayMode: value })
					}
				),
				wp.element.createElement(
					SelectControl,
					{
						label: 'Sparte',
						value: sparte || '',
						options: [
							{ label: 'Bitte auswählen...', value: '' },
							...sparten
						],
						onChange: (value) => setAttributes({ sparte: value }),
						disabled: loading
					}
				),
				(displayMode || 'single') === 'single' && wp.element.createElement(
					SelectControl,
					{
						label: 'Funktion',
						value: funktion || '',
						options: [
							{ label: 'Bitte auswählen...', value: '' },
							...funktionen
						],
						onChange: (value) => setAttributes({ funktion: value }),
						disabled: !sparte || funktionen.length === 0
					}
				)
			)
		),
		wp.element.createElement(
			'div',
			blockProps,
			wp.element.createElement(
				'div',
				{ className: 'war-editor-preview' },
				!sparte ?
					wp.element.createElement(
						'p',
						{ style: { padding: '20px', textAlign: 'center', color: '#999' } },
						'Bitte wählen Sie eine Sparte aus'
					)
					:
					wp.element.createElement(
						'div',
						{ style: { padding: '20px', backgroundColor: '#f5f5f5', borderRadius: '4px' } },
						wp.element.createElement(
							'p',
							{ style: { margin: '5px 0', fontSize: '14px' } },
							wp.element.createElement('strong', null, 'Modus: '),
							(displayMode || 'single') === 'single' ? 'Einzelne Karte' : 'Alle untereinander'
						),
						wp.element.createElement(
							'p',
							{ style: { margin: '5px 0', fontSize: '14px' } },
							wp.element.createElement('strong', null, 'Sparte: '),
							sparte
						),
						(displayMode || 'single') === 'single' && wp.element.createElement(
							'p',
							{ style: { margin: '5px 0', fontSize: '14px' } },
							wp.element.createElement('strong', null, 'Funktion: '),
							funktion || '(Alle)'
						)
					)
			)
		)
	);
}

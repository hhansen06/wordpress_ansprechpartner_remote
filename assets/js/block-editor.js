const { registerBlockType } = wp.blocks;
const { useBlockProps, InspectorControls } = wp.blockEditor;
const { PanelBody, SelectControl, RadioControl, CheckboxControl, ColorPalette, TextControl } = wp.components;
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

    const { displayMode, sparte, funktionen: selectedFunktionen, startColor = '#667eea', endColor = '#764ba2', cardLayout = 'grid', overrideEmail = '' } = attributes;

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

    // Funktionen laden wenn Sparte gesetzt/geändert wird
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

                // Filter ungültig gewordene Funktionen
                const validFuncs = new Set(Array.from(funcs));
                const updatedSelected = (selectedFunktionen || []).filter(f => validFuncs.has(f));
                if (updatedSelected.length !== (selectedFunktionen || []).length) {
                    setAttributes({ funktionen: updatedSelected });
                }
            } catch (error) {
                console.error('Fehler beim Laden der Funktionen:', error);
                setFunktionen([]);
            }
        };

        loadFunktionen();
    }, [sparte]);

    // Handler für Funktionen Veränderung
    const handleFunktionChange = (funktionValue, isChecked) => {
        const updated = isChecked
            ? [...(selectedFunktionen || []), funktionValue]
            : (selectedFunktionen || []).filter(f => f !== funktionValue);
        setAttributes({ funktionen: updated });
    };

    // Handler zum Verschieben von Funktionen
    const moveFunktion = (index, direction) => {
        const updated = [...(selectedFunktionen || [])];
        const newIndex = direction === 'up' ? index - 1 : index + 1;

        if (newIndex >= 0 && newIndex < updated.length) {
            [updated[index], updated[newIndex]] = [updated[newIndex], updated[index]];
            setAttributes({ funktionen: updated });
        }
    };

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
                (displayMode || 'single') === 'single' && sparte && funktionen.length > 0 && wp.element.createElement(
                    Fragment,
                    null,
                    wp.element.createElement(
                        'p',
                        { style: { marginBottom: '10px', fontWeight: 'bold' } },
                        'Funktionen auswählen (optional)'
                    ),
                    funktionen.map(funktion =>
                        wp.element.createElement(
                            CheckboxControl,
                            {
                                key: funktion.value,
                                label: funktion.label,
                                checked: (selectedFunktionen || []).includes(funktion.value),
                                onChange: (isChecked) => handleFunktionChange(funktion.value, isChecked)
                            }
                        )
                    ),
                    (selectedFunktionen || []).length > 1 && wp.element.createElement(
                        Fragment,
                        null,
                        wp.element.createElement(
                            'hr',
                            { style: { margin: '15px 0', borderColor: '#ddd' } }
                        ),
                        wp.element.createElement(
                            'p',
                            { style: { marginBottom: '10px', fontWeight: 'bold' } },
                            'Reihenfolge der Funktionen'
                        ),
                        (selectedFunktionen || []).map((funktion, index) =>
                            wp.element.createElement(
                                'div',
                                {
                                    key: funktion,
                                    style: {
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '8px',
                                        padding: '8px',
                                        backgroundColor: '#f9f9f9',
                                        borderRadius: '3px',
                                        marginBottom: '6px'
                                    }
                                },
                                wp.element.createElement(
                                    'span',
                                    { style: { flex: 1, fontSize: '13px' } },
                                    funktion
                                ),
                                wp.element.createElement(
                                    'button',
                                    {
                                        onClick: () => moveFunktion(index, 'up'),
                                        disabled: index === 0,
                                        style: {
                                            padding: '4px 8px',
                                            fontSize: '12px',
                                            cursor: index === 0 ? 'default' : 'pointer',
                                            opacity: index === 0 ? 0.5 : 1,
                                            border: '1px solid #999',
                                            backgroundColor: '#fff',
                                            borderRadius: '3px'
                                        }
                                    },
                                    '↑'
                                ),
                                wp.element.createElement(
                                    'button',
                                    {
                                        onClick: () => moveFunktion(index, 'down'),
                                        disabled: index === (selectedFunktionen || []).length - 1,
                                        style: {
                                            padding: '4px 8px',
                                            fontSize: '12px',
                                            cursor: index === (selectedFunktionen || []).length - 1 ? 'default' : 'pointer',
                                            opacity: index === (selectedFunktionen || []).length - 1 ? 0.5 : 1,
                                            border: '1px solid #999',
                                            backgroundColor: '#fff',
                                            borderRadius: '3px'
                                        }
                                    },
                                    '↓'
                                )
                            )
                        )
                    )
                ),
                wp.element.createElement(
                    'hr',
                    { style: { margin: '15px 0' } }
                ),
                wp.element.createElement(
                    'p',
                    { style: { marginBottom: '10px', fontWeight: 'bold' } },
                    'Farbverlauf'
                ),
                wp.element.createElement(
                    Fragment,
                    null,
                    wp.element.createElement(
                        'p',
                        { style: { marginBottom: '8px', fontSize: '13px', color: '#666' } },
                        'Start-Farbe'
                    ),
                    wp.element.createElement(
                        ColorPalette,
                        {
                            value: startColor,
                            onChange: (color) => setAttributes({ startColor: color }),
                            allowCustom: true
                        }
                    )
                ),
                wp.element.createElement(
                    Fragment,
                    null,
                    wp.element.createElement(
                        'p',
                        { style: { marginBottom: '8px', fontSize: '13px', color: '#666', marginTop: '15px' } },
                        'End-Farbe'
                    ),
                    wp.element.createElement(
                        ColorPalette,
                        {
                            value: endColor,
                            onChange: (color) => setAttributes({ endColor: color }),
                            allowCustom: true
                        }
                    )
                ),
                wp.element.createElement(
                    'hr',
                    { style: { margin: '15px 0' } }
                ),
                wp.element.createElement(
                    'p',
                    { style: { marginBottom: '10px', fontWeight: 'bold' } },
                    'Layout'
                ),
                wp.element.createElement(
                    RadioControl,
                    {
                        label: 'Card Layout',
                        selected: cardLayout || 'grid',
                        options: [
                            { label: 'Grid (Standard)', value: 'grid' },
                            { label: 'Horizontal (Schmale Variante)', value: 'horizontal' }
                        ],
                        onChange: (value) => setAttributes({ cardLayout: value })
                    }
                ),
                wp.element.createElement(
                    'hr',
                    { style: { margin: '15px 0' } }
                ),
                wp.element.createElement(
                    'p',
                    { style: { marginBottom: '10px', fontWeight: 'bold' } },
                    'Email Einstellungen'
                ),
                wp.element.createElement(
                    TextControl,
                    {
                        label: 'Email überschreiben (optional)',
                        value: overrideEmail,
                        onChange: (value) => setAttributes({ overrideEmail: value }),
                        placeholder: 'z.B. kontakt@example.com',
                        help: 'Wenn gesetzt, wird diese Email für alle Personen verwendet'
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
                        (displayMode || 'single') === 'single' && (selectedFunktionen || []).length > 0 && wp.element.createElement(
                            'p',
                            { style: { margin: '5px 0', fontSize: '14px' } },
                            wp.element.createElement('strong', null, 'Funktionen: '),
                            (selectedFunktionen || []).join(', ')
                        ),
                        wp.element.createElement(
                            'p',
                            { style: { margin: '15px 0 5px 0', fontSize: '14px' } },
                            wp.element.createElement('strong', null, 'Farbverlauf: ')
                        ),
                        wp.element.createElement(
                            'div',
                            {
                                style: {
                                    background: 'linear-gradient(135deg, ' + startColor + ' 0%, ' + endColor + ' 100%)',
                                    height: '40px',
                                    borderRadius: '4px',
                                    marginTop: '8px'
                                }
                            }
                        )
                    )
            )
        )
    );
}

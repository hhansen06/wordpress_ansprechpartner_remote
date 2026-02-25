const { registerBlockType } = wp.blocks;
const { useBlockProps, InspectorControls } = wp.blockEditor;
const { PanelBody, SelectControl, RadioControl } = wp.components;
const { useEffect, useState } = wp.element;
const apiFetch = wp.apiFetch;

registerBlockType('war/ansprechpartner', {
    edit: EditComponent,
    save: () => null, // Server-side rendering
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
                const response = await apiFetch({
                    path: '/vereinsverwaltung/v1/sparten',
                });

                // Transform data for SelectControl
                const options = Array.isArray(response)
                    ? response.map(s => ({
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
                const response = await apiFetch({
                    path: `/vereinsverwaltung/v1/ansprechpartner/${encodeURIComponent(sparte)}`,
                });

                // Unique Funktionen sammeln
                const funcs = new Set();
                if (Array.isArray(response)) {
                    response.forEach(person => {
                        if (person.funktion) {
                            funcs.add(person.funktion);
                        }
                    });
                }

                const options = Array.from(funcs).map(f => ({
                    label: f,
                    value: f
                }));

                setFunktionen(options);

                // Funktion zurücksetzen wenn nicht mehr vorhanden
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

    return (
        <>
            <InspectorControls>
                <PanelBody title="Ansprechpartner Einstellungen">
                    <RadioControl
                        label="Anzeigemodus"
                        selected={displayMode || 'single'}
                        options={[
                            { label: 'Einzelne Karte', value: 'single' },
                            { label: 'Alle untereinander', value: 'all' }
                        ]}
                        onChange={(value) => setAttributes({ displayMode: value })}
                    />

                    <SelectControl
                        label="Sparte"
                        value={sparte || ''}
                        options={[
                            { label: 'Bitte auswählen...', value: '' },
                            ...sparten
                        ]}
                        onChange={(value) => {
                            setAttributes({ sparte: value });
                        }}
                        disabled={loading}
                    />

                    {(displayMode || 'single') === 'single' && (
                        <SelectControl
                            label="Funktion"
                            value={funktion || ''}
                            options={[
                                { label: 'Bitte auswählen...', value: '' },
                                ...funktionen
                            ]}
                            onChange={(value) => setAttributes({ funktion: value })}
                            disabled={!sparte || funktionen.length === 0}
                        />
                    )}
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <div className="war-editor-preview">
                    {!sparte ? (
                        <p style={{ padding: '20px', textAlign: 'center', color: '#999' }}>
                            Bitte wählen Sie eine Sparte aus
                        </p>
                    ) : (
                        <div style={{ padding: '20px', backgroundColor: '#f5f5f5', borderRadius: '4px' }}>
                            <p style={{ margin: '5px 0', fontSize: '14px' }}>
                                <strong>Modus:</strong> {(displayMode || 'single') === 'single' ? 'Einzelne Karte' : 'Alle untereinander'}
                            </p>
                            <p style={{ margin: '5px 0', fontSize: '14px' }}>
                                <strong>Sparte:</strong> {sparte}
                            </p>
                            {(displayMode || 'single') === 'single' && (
                                <p style={{ margin: '5px 0', fontSize: '14px' }}>
                                    <strong>Funktion:</strong> {funktion || '(Alle)'}
                                </p>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

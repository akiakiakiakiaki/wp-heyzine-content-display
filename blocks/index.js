const { registerBlockType } = wp.blocks;
const { SelectControl, CheckboxControl, TextControl } = wp.components;
const { InspectorControls } = wp.blockEditor || wp.editor;
const { PanelBody } = wp.components;
const { useState, useEffect, createElement, Fragment } = wp.element;
const { __ } = wp.i18n;

function normalizeDimension(value, fallback) {
    const normalized = String(value || fallback).trim().toLowerCase();
    const match = normalized.match(/^([0-9]+(?:\.[0-9]+)?)(px|%|vh|vw|rem|em)?$/);
    if (!match) {
        return fallback;
    }

    const amount = Number(match[1]);
    const unit = match[2] || '';
    const maximum = ['%', 'vh', 'vw'].includes(unit) ? 100 : (['rem', 'em'].includes(unit) ? 1000 : 10000);
    return amount > 0 && amount <= maximum ? `${match[1]}${unit}` : fallback;
}

function isSafeHeyzineEmbedUrl(value) {
    try {
        const url = new URL(value);
        return url.protocol === 'https:' && ['heyzine.com', 'www.heyzine.com'].includes(url.hostname.toLowerCase()) && (!url.port || url.port === '443');
    } catch (error) {
        return false;
    }
}

function renderFlipbookIframe(embedUrl, width, height, isEditor = false) {
    const defaultWidth = (typeof hcdFlipbookDefaults !== 'undefined' && hcdFlipbookDefaults.width) ? hcdFlipbookDefaults.width : '100%';
    const defaultHeight = (typeof hcdFlipbookDefaults !== 'undefined' && hcdFlipbookDefaults.height) ? hcdFlipbookDefaults.height : '600';
    if (!isSafeHeyzineEmbedUrl(embedUrl)) {
        return createElement('p', null, __('Invalid Heyzine embed URL.', 'heyzine-content-display'));
    }

    const renderedWidth = normalizeDimension(width, defaultWidth);
    const renderedHeight = normalizeDimension(height, defaultHeight);
    const iframeStyle = isEditor ? {
        border: 0,
        pointerEvents: 'none',
        width: /^\d+(?:\.\d+)?$/.test(String(renderedWidth).trim()) ? `${renderedWidth}px` : renderedWidth,
        height: /^\d+(?:\.\d+)?$/.test(String(renderedHeight).trim()) ? `${renderedHeight}px` : renderedHeight,
    } : { border: 0 };
    const iframe = createElement('iframe', {
        src: embedUrl,
        width: renderedWidth,
        height: renderedHeight,
        style: iframeStyle,
        allowFullScreen: true,
        tabIndex: isEditor ? -1 : undefined,
    });

    if (!isEditor) {
        return iframe;
    }

    const previewStyle = {
        width: '100%',
        display: 'flex',
        justifyContent: 'center',
    };
    if (String(renderedHeight).trim().endsWith('%')) {
        previewStyle.height = '87vh';
        previewStyle.minHeight = '400px';
    }

    return createElement('div', { className: 'hcd-flipbook-editor-preview', style: previewStyle }, iframe);
}

registerBlockType('heyzine/flipbook-embed', {
    title: __('Heyzine Flipbook', 'heyzine-content-display'),
    icon: 'book',
    category: 'widgets',
    attributes: {
        flipbookId: {
            type: 'string',
            default: ''
        },
        showNewest: {
            type: 'boolean',
            default: false
        },
        width: {
            type: 'string',
            default: ''
        },
        height: {
            type: 'string',
            default: ''
        }
    },
    edit: ({ attributes, setAttributes }) => {
        const [flipbooks, setFlipbooks] = useState([]);

        useEffect(() => {
            fetch(hcdFlipbookAjax.url + '?action=hcd_get_flipbooks&nonce=' + encodeURIComponent(hcdFlipbookAjax.nonce))
                .then(res => res.json())
                .then(data => setFlipbooks(data));
        }, []);

        return createElement(
            Fragment,
            null,
            createElement(
                InspectorControls,
                null,
                createElement(
                    PanelBody,
                    { title: __('Select a flipbook', 'heyzine-content-display') },
                    createElement(CheckboxControl, {
                        label: __('Show newest flipbook', 'heyzine-content-display'),
                        checked: attributes.showNewest,
                        onChange: (value) => setAttributes({ showNewest: value }),
                    }),
                    createElement(SelectControl, {
                        label: __('Select a flipbook', 'heyzine-content-display'),
                        value: attributes.flipbookId,
                        options: flipbooks.map(fb => ({
                            label: fb.title && fb.title.trim() !== '' ? fb.title : fb.date,
                            value: fb.id
                        })),
                        onChange: (id) => setAttributes({ flipbookId: id }),
                        disabled: attributes.showNewest
                    }),
                    createElement(TextControl, {
                        label: __('Width', 'heyzine-content-display'),
                        value: attributes.width,
                        onChange: (value) => setAttributes({ width: value }),
                        help: __('Leave empty to use the default value.', 'heyzine-content-display')
                    }),
                    createElement(TextControl, {
                        label: __('Height', 'heyzine-content-display'),
                        value: attributes.height,
                        onChange: (value) => setAttributes({ height: value }),
                        help: __('Leave empty to use the default value.', 'heyzine-content-display')
                    })
                )
            ),
            createElement(
                'div',
                null,
                (() => {
                    // Show iframe for the selected or newest flipbook
                    if (attributes.showNewest) {
                        // Show newest flipbook (first in the list)
                        if (flipbooks && flipbooks.length > 0 && flipbooks[0].embed) {
                            return renderFlipbookIframe(flipbooks[0].embed, attributes.width || undefined, attributes.height || undefined, true);
                        } else {
                            return createElement('p', null, __('No flipbook selected.', 'heyzine-content-display'));
                        }
                    } else if (attributes.flipbookId) {
                        // Find the selected flipbook
                        const fb = flipbooks.find(fb => fb.id === attributes.flipbookId);
                        if (fb && fb.embed) {
                            return renderFlipbookIframe(fb.embed, attributes.width || undefined, attributes.height || undefined, true);
                        } else {
                            return createElement('p', null, __('No flipbook selected.', 'heyzine-content-display'));
                        }
                    } else {
                        return createElement('p', null, __('No flipbook selected.', 'heyzine-content-display'));
                    }
                })()
            )
        );
    },
    save: () => {
        // Render via php callback
        return null;
    }
});

registerBlockType('heyzine/flipbook-list', {
    title: __('Heyzine Flipbook List', 'heyzine-content-display'),
    icon: 'list-view',
    category: 'widgets',
    attributes: {
        start: {
            type: 'number',
            default: 0
        },
        count: {
            type: 'number',
            default: 10
        },
        order: {
            type: 'string',
            default: 'desc'
        },
        columns_mobile: {
            type: 'number',
            default: 2
        },
        columns_tablet: {
            type: 'number',
            default: 3
        },
        columns_desktop: {
            type: 'number',
            default: 5
        },
        show_titles: {
            type: 'boolean',
            default: false
        },
        open_in_new_window: {
            type: 'boolean',
            default: true
        },
        open_on_site_embed: {
            type: 'boolean',
            default: true
        }
    },
    edit: ({ attributes, setAttributes }) => {
        const {
            start,
            count,
            order,
            columns_mobile,
            columns_tablet,
            columns_desktop,
            show_titles,
            open_in_new_window,
            open_on_site_embed
        } = attributes;

        const [flipbooks, setFlipbooks] = useState([]);

        useEffect(() => {
            fetch(hcdFlipbookAjax.url + '?action=hcd_get_flipbooks&nonce=' + encodeURIComponent(hcdFlipbookAjax.nonce))
                .then(res => res.json())
                .then(data => {
                    // catch api errors
                    if (typeof data.success === 'boolean' && data.success === false) {
                        console.error(__('Heyzine API error:', 'heyzine-content-display'), data.msg || __('Unknown error.', 'heyzine-content-display'));
                        setFlipbooks([]);
                        return;
                    }
                    if (!Array.isArray(data)) {
                        console.error(__('Invalid API response.', 'heyzine-content-display'), data);
                        setFlipbooks([]);
                        return;
                    }
                    // Sort flipbooks according to order
                    let sorted = data.slice();
                    if (order === 'asc') {
                        sorted.sort((a, b) => a.id.localeCompare(b.id));
                    } else {
                        sorted.sort((a, b) => b.id.localeCompare(a.id));
                    }
                    // Slice according to start and count
                    const sliced = sorted.slice(start, start + count);
                    setFlipbooks(sliced);
                })
                .catch(err => {
                    console.error(__('Fetch or network error:', 'heyzine-content-display'), err);
                    setFlipbooks([]);
                });
        }, [start, count, order]);

        // Styles for grid with responsive columns
        const gridStyle = {
            display: 'grid',
            gap: '10px',
            pointerEvents: 'none',
            alignItems: 'center',
            // gridTemplateColumns: `repeat(${columns_desktop}, 1fr)`,
        };

        const mobileTablet = (typeof hcdFlipbookBreakpoints !== 'undefined') ? hcdFlipbookBreakpoints.mobileTablet : 600;
        const tabletDesktop = (typeof hcdFlipbookBreakpoints !== 'undefined') ? hcdFlipbookBreakpoints.tabletDesktop : 782;

        const responsiveStyles = `
            @media (max-width: ${mobileTablet}px) {
                .hcd-preview-grid { grid-template-columns: repeat(${columns_mobile}, 1fr); }
            }
            @media (min-width: ${parseInt(mobileTablet) + 1}px) and (max-width: ${tabletDesktop}px) {
                .hcd-preview-grid { grid-template-columns: repeat(${columns_tablet}, 1fr); }
            }
            @media (min-width: ${parseInt(tabletDesktop) + 1}px) {
                .hcd-preview-grid { grid-template-columns: repeat(${columns_desktop}, 1fr); }
            }
        `;

        return createElement(
            Fragment,
            null,
            createElement(
                InspectorControls,
                null,
                createElement(
                    PanelBody,
                    { title: __('Flipbook list settings', 'heyzine-content-display') },
                    createElement(TextControl, {
                        label: "Start",
                        type: "number",
                        value: start,
                        onChange: (value) => setAttributes({ start: parseInt(value) || 0 }),
                        help: __('Starting index in the list.', 'heyzine-content-display')
                    }),
                    createElement(TextControl, {
                        label: __('Number of items', 'heyzine-content-display'),
                        type: "number",
                        value: count,
                        onChange: (value) => setAttributes({ count: parseInt(value) || 10 }),
                        help: __('Number of flipbooks to display.', 'heyzine-content-display')
                    }),
                    createElement(SelectControl, {
                        label: __('Sort order', 'heyzine-content-display'),
                        value: order,
                        options: [
                            { label: __('Descending (newest first)', 'heyzine-content-display'), value: 'desc' },
                            { label: __('Ascending (oldest first)', 'heyzine-content-display'), value: 'asc' }
                        ],
                        onChange: (value) => setAttributes({ order: value }),
                    }),
                    createElement(TextControl, {
                        label: __('Columns (mobile)', 'heyzine-content-display'),
                        type: "number",
                        value: columns_mobile,
                        onChange: (value) => setAttributes({ columns_mobile: parseInt(value) || 2 }),
                    }),
                    createElement(TextControl, {
                        label: __('Columns (tablet)', 'heyzine-content-display'),
                        type: "number",
                        value: columns_tablet,
                        onChange: (value) => setAttributes({ columns_tablet: parseInt(value) || 3 }),
                    }),
                    createElement(TextControl, {
                        label: __('Columns (desktop)', 'heyzine-content-display'),
                        type: "number",
                        value: columns_desktop,
                        onChange: (value) => setAttributes({ columns_desktop: parseInt(value) || 5 }),
                    }),
                    createElement(CheckboxControl, {
                        label: __('Show titles', 'heyzine-content-display'),
                        checked: show_titles,
                        onChange: (value) => setAttributes({ show_titles: value }),
                    }),
                    createElement(CheckboxControl, {
                        label: __('Open links in a new tab', 'heyzine-content-display'),
                        checked: open_in_new_window,
                        onChange: (value) => setAttributes({ open_in_new_window: value }),
                    }),
                    createElement(CheckboxControl, {
                        label: __('Open flipbooks in an on-site embed', 'heyzine-content-display'),
                        checked: open_on_site_embed,
                        onChange: (value) => setAttributes({ open_on_site_embed: value }),
                    })
                )
            ),
            createElement('style', null, responsiveStyles),
            createElement(
                'div',
                { className: 'hcd-preview-grid', style: gridStyle },
                flipbooks.map(fb => createElement(
                    'div',
                    {
                        key: fb.id,
                        style: {
                            textAlign: 'center',
                            userSelect: 'none',
                            pointerEvents: 'none',
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                        }
                    },
                    createElement('img', {
                        src: fb.links && fb.links.thumbnail ? fb.links.thumbnail : '',
                        alt: fb.title || '',
                        style: {
                            maxWidth: '100%',
                            height: 'auto',
                            marginBottom: show_titles ? '8px' : '0',
                            pointerEvents: 'none',
                            userSelect: 'none',
                        }
                    }),
                    show_titles ? createElement('div', { style: { pointerEvents: 'none', userSelect: 'none' } }, fb.title) : null
                ))
            )
        );
    },
    save: () => {
        return null;
    }
});

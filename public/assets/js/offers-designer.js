(function () {
    const root = document.querySelector('[data-designer-root]');
    if (!root || typeof window.grapesjs === 'undefined') {
        return;
    }

    const config = window.offerDesigner || {};
    const toast = typeof window.toast === 'object' ? window.toast : null;

    const state = {
        currentDesignId: null,
        isDirty: false,
        previewActive: false,
    };

    const tabs = root.querySelectorAll('[data-designer-tab]');
    const panels = root.querySelectorAll('[data-designer-panel]');
    const designList = root.querySelector('[data-design-list]');
    const templateButtons = root.querySelectorAll('.canvas-template');
    const nameInput = root.querySelector('#designer-name');
    const notesInput = root.querySelector('#designer-notes');
    const formatSelect = root.querySelector('#designer-format');
    const orientationSelect = root.querySelector('#designer-orientation');
    const exportForm = root.querySelector('#designer-export-form');
    const exportPayloadInput = exportForm ? exportForm.querySelector('input[name="canvas_payload"]') : null;
    const canvasWrapper = root.querySelector('[data-canvas-wrapper]');

    const defaults = config.defaults || { name: 'Layout brochure', format: 'A4', orientation: 'portrait', theme: 'aurora' };
    const templates = Array.isArray(config.templates) ? config.templates : [];
    const endpoints = config.endpoints || {};

    const PAPER_SIZES = {
        A4: { portrait: { width: 210, height: 297 }, landscape: { width: 297, height: 210 } },
        A3: { portrait: { width: 297, height: 420 }, landscape: { width: 420, height: 297 } },
    };

    const editor = window.grapesjs.init({
        container: '#designer-canvas',
        height: 'calc(100vh - 320px)',
        width: 'auto',
        fromElement: false,
        storageManager: false,
        selectorManager: { componentFirst: true },
        blockManager: { appendTo: null },
        styleManager: { sectors: null },
        plugins: ['gjs-preset-webpage'],
        pluginsOpts: {
            'gjs-preset-webpage': {
                modalImportTitle: 'Importa markup personalizzato',
                modalImportLabel: '',
                customStyleManager: []
            }
        }
    });

    function notify(type, message) {
        if (toast && typeof toast[type] === 'function') {
            toast[type](message);
        } else {
            console[type === 'danger' ? 'error' : 'log'](message);
        }
    }

    function slugify(value) {
        if (!value) {
            return 'brochure';
        }
        return value
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .substring(0, 60) || 'brochure';
    }

    function setActiveMode(mode) {
        tabs.forEach(tab => {
            const isActive = tab.dataset.designerTab === mode;
            tab.classList.toggle('designer-tab--active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        panels.forEach(panel => {
            const isActive = panel.dataset.designerPanel === mode;
            panel.classList.toggle('designer-panel--active', isActive);
            if (isActive) {
                panel.removeAttribute('hidden');
            } else {
                panel.setAttribute('hidden', 'true');
            }
        });
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const mode = tab.dataset.designerTab;
            if (mode) {
                setActiveMode(mode);
            }
        });
    });

    function getPaperSize(format, orientation) {
        const fmt = PAPER_SIZES[format] || PAPER_SIZES.A4;
        return fmt[orientation] || fmt.portrait;
    }

    function applyPaperSize() {
        const format = formatSelect.value || 'A4';
        const orientation = orientationSelect.value || 'portrait';
        const size = getPaperSize(format, orientation);
        const frame = editor.Canvas.getFrameEl();
        const canvasEl = editor.Canvas.getCanvasView().el;
        if (canvasEl) {
            canvasEl.style.background = '#f1f5f9';
            canvasEl.style.display = 'flex';
            canvasEl.style.alignItems = 'center';
            canvasEl.style.justifyContent = 'center';
            canvasEl.style.padding = '32px';
        }
        if (frame) {
            frame.style.width = `${size.width}mm`;
            frame.style.minWidth = `${size.width}mm`;
            frame.style.height = `${size.height}mm`;
            frame.style.minHeight = `${size.height}mm`;
            frame.style.boxShadow = '0 34px 80px rgba(15, 23, 42, 0.25)';
            frame.style.borderRadius = '22px';
            frame.style.background = '#ffffff';
        }
        if (canvasWrapper) {
            canvasWrapper.style.setProperty('--paper-width', `${size.width}mm`);
            canvasWrapper.style.setProperty('--paper-height', `${size.height}mm`);
        }
    }

    function setDirty(isDirty) {
        state.isDirty = isDirty;
        root.dataset.designerDirty = isDirty ? 'true' : 'false';
    }

    function highlightActiveDesign(designId) {
        if (!designList) {
            return;
        }
        designList.querySelectorAll('.canvas-design-card').forEach(item => {
            const matches = item.dataset.designId === designId;
            item.classList.toggle('canvas-design-card--active', matches);
        });
    }

    function resetCanvas() {
        editor.DomComponents.clear();
        editor.CssComposer.getAll().reset();
        editor.UndoManager.clear();
        nameInput.value = defaults.name || 'Brochure personalizzata';
        notesInput.value = '';
        formatSelect.value = defaults.format || 'A4';
        orientationSelect.value = defaults.orientation || 'portrait';
        state.currentDesignId = null;
        highlightActiveDesign(null);
        applyPaperSize();
        setDirty(false);
    }

    function loadTemplate(templateId) {
        const template = templates.find(item => item.id === templateId);
        if (!template) {
            notify('warning', 'Template non disponibile.');
            return;
        }
        editor.setComponents(template.html || '');
        editor.setStyle(template.css || '');
        formatSelect.value = template.format || 'A4';
        orientationSelect.value = template.orientation || 'portrait';
        nameInput.value = template.name || defaults.name || 'Brochure personalizzata';
        notesInput.value = template.description || '';
        state.currentDesignId = null;
        highlightActiveDesign(null);
        applyPaperSize();
        setDirty(true);
        notify('info', `Template "${template.name}" applicato.`);
    }

    function renderDesignList(items) {
        if (!designList) {
            return;
        }
        designList.innerHTML = '';
        if (!items || items.length === 0) {
            const empty = document.createElement('li');
            empty.className = 'canvas-design-card canvas-design-card--empty';
            empty.textContent = 'Ancora nessun layout salvato.';
            designList.appendChild(empty);
            return;
        }
        items.forEach(item => {
            const li = document.createElement('li');
            li.className = 'canvas-design-card';
            li.dataset.designId = item.id;
            li.innerHTML = `
                <div class="canvas-design-card__main">
                    <strong>${(item.name || 'Layout').replace(/</g, '&lt;')}</strong>
                    <span>${(item.format || 'A4').toUpperCase()} · ${(item.orientation || 'portrait')}</span>
                </div>
                <div class="canvas-design-card__actions">
                    <button type="button" class="canvas-design-card__action" data-designer-action="load-design">Apri</button>
                    <button type="button" class="canvas-design-card__action canvas-design-card__action--danger" data-designer-action="delete-design">Elimina</button>
                </div>
            `;
            designList.appendChild(li);
        });
        highlightActiveDesign(state.currentDesignId);
    }

    function collectPayload() {
        const name = nameInput.value.trim() || defaults.name || 'Brochure personalizzata';
        const format = formatSelect.value || 'A4';
        const orientation = orientationSelect.value || 'portrait';
        const html = editor.getHtml({ cleanCss: true });
        const css = editor.getCss();
        const projectData = editor.getProjectData ? editor.getProjectData() : null;
        return {
            public_id: state.currentDesignId,
            name,
            description: notesInput.value.trim(),
            format,
            orientation,
            theme: defaults.theme || 'aurora',
            html,
            css,
            design_json: projectData ? JSON.stringify(projectData) : null,
            meta: {
                title: name,
                filename_prefix: slugify(name),
                notes: notesInput.value.trim() || null,
            },
        };
    }

    async function saveDesign() {
        const payload = collectPayload();
        try {
            const response = await fetch(endpoints.save, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                credentials: 'same-origin',
            });
            const json = await response.json();
            if (!response.ok || !json.success) {
                throw new Error(json.message || 'Salvataggio non riuscito.');
            }
            state.currentDesignId = json.design?.id || json.design?.public_id || payload.public_id || null;
            setDirty(false);
            notify('success', json.message || 'Layout salvato con successo.');
            await refreshDesignList();
            highlightActiveDesign(state.currentDesignId);
        } catch (error) {
            notify('danger', error.message || 'Errore durante il salvataggio del layout.');
        }
    }

    async function refreshDesignList() {
        try {
            const response = await fetch(endpoints.list, { credentials: 'same-origin' });
            const json = await response.json();
            if (!response.ok || !json.success) {
                throw new Error(json.message || 'Impossibile recuperare i layout.');
            }
            renderDesignList(Array.isArray(json.designs) ? json.designs : []);
        } catch (error) {
            notify('warning', error.message || 'Non è stato possibile aggiornare i layout salvati.');
        }
    }

    async function loadDesign(designId) {
        try {
            const url = `${endpoints.load}&design=${encodeURIComponent(designId)}`;
            const response = await fetch(url, { credentials: 'same-origin' });
            const json = await response.json();
            if (!response.ok || !json.success || !json.design) {
                throw new Error(json.message || 'Layout non trovato.');
            }
            const design = json.design;
            if (design.design_json) {
                try {
                    const project = JSON.parse(design.design_json);
                    editor.loadProjectData(project);
                } catch (error) {
                    editor.setComponents(design.html || '');
                    editor.setStyle(design.css || '');
                }
            } else {
                editor.setComponents(design.html || '');
                editor.setStyle(design.css || '');
            }
            nameInput.value = design.name || defaults.name || 'Brochure personalizzata';
            notesInput.value = design.description || '';
            formatSelect.value = design.format || 'A4';
            orientationSelect.value = design.orientation || 'portrait';
            state.currentDesignId = design.id || design.public_id || designId;
            highlightActiveDesign(state.currentDesignId);
            applyPaperSize();
            setDirty(false);
            notify('success', 'Layout caricato.');
        } catch (error) {
            notify('danger', error.message || 'Impossibile caricare il layout selezionato.');
        }
    }

    async function deleteDesign(designId) {
        if (!designId) {
            return;
        }
        if (!window.confirm('Eliminare definitivamente il layout?')) {
            return;
        }
        try {
            const response = await fetch(endpoints.delete, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ design_id: designId }),
                credentials: 'same-origin',
            });
            const json = await response.json();
            if (!response.ok || !json.success) {
                throw new Error(json.message || 'Eliminazione fallita.');
            }
            if (state.currentDesignId === designId) {
                state.currentDesignId = null;
                resetCanvas();
            }
            await refreshDesignList();
            notify('success', json.message || 'Layout eliminato.');
        } catch (error) {
            notify('danger', error.message || 'Impossibile eliminare il layout selezionato.');
        }
    }

    function exportDesign() {
        if (!exportForm || !exportPayloadInput) {
            notify('danger', 'Modulo di esportazione non disponibile.');
            return;
        }
        const payload = collectPayload();
        payload.design_id = state.currentDesignId;
        exportPayloadInput.value = JSON.stringify(payload);
        exportForm.submit();
    }

    function togglePreview() {
        state.previewActive = !state.previewActive;
        editor.runCommand('core:preview');
    }

    function handleAction(event) {
        const trigger = event.target.closest('[data-designer-action]');
        if (!trigger) {
            return;
        }
        const action = trigger.dataset.designerAction;
        switch (action) {
            case 'new':
                resetCanvas();
                notify('info', 'Nuovo layout vuoto pronto.');
                break;
            case 'save':
                saveDesign();
                break;
            case 'refresh':
                refreshDesignList();
                break;
            case 'export':
                exportDesign();
                break;
            case 'reset':
                if (!state.isDirty || window.confirm('Vuoi davvero cancellare le modifiche correnti?')) {
                    resetCanvas();
                }
                break;
            case 'snapshot':
                togglePreview();
                break;
            case 'load-design': {
                const item = trigger.closest('.canvas-design-card');
                if (item) {
                    loadDesign(item.dataset.designId || '');
                }
                break;
            }
            case 'delete-design': {
                const item = trigger.closest('.canvas-design-card');
                if (item) {
                    deleteDesign(item.dataset.designId || '');
                }
                break;
            }
            default:
                break;
        }
    }

    root.addEventListener('click', handleAction);

    templateButtons.forEach(button => {
        button.addEventListener('click', () => {
            const templateId = button.dataset.template;
            if (templateId) {
                loadTemplate(templateId);
            }
        });
    });

    if (designList) {
        designList.addEventListener('click', event => {
            const trigger = event.target.closest('[data-designer-action]');
            if (trigger) {
                event.preventDefault();
            }
        });
    }

    orientationSelect.addEventListener('change', applyPaperSize);
    formatSelect.addEventListener('change', applyPaperSize);

    editor.on('component:add component:remove component:update style:change', () => setDirty(true));

    resetCanvas();
    applyPaperSize();
    renderDesignList(Array.isArray(config.designs) ? config.designs : []);
})();

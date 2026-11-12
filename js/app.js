(function () {
    'use strict';

    var config = window.APP_CONFIG;
    var entities = {
        productos: {
            label: 'Productos',
            singular: 'producto',
            description: 'Administrá los productos disponibles en el sistema.',
            icon: '▦',
            fields: [
                { name: 'nombre', label: 'Nombre', required: true, maxLength: 100 },
                { name: 'descripcion', label: 'Descripción', type: 'textarea', nullable: true, maxLength: 65535 },
                { name: 'precio', label: 'Precio', type: 'number', required: true, min: 0, step: '0.01' },
                { name: 'stock', label: 'Stock', type: 'number', required: true, min: 0, step: '1' },
                { name: 'imagen', label: 'URL de imagen', nullable: true, maxLength: 255 },
                { name: 'categoria_id', label: 'ID de categoría', type: 'number', nullable: true, min: 1, step: '1' }
            ]
        },
        categorias: {
            label: 'Categorías',
            singular: 'categoría',
            description: 'Organizá los productos en categorías.',
            icon: '▤',
            fields: [
                { name: 'nombre', label: 'Nombre', required: true, maxLength: 100 }
            ]
        },
        clientes: {
            label: 'Clientes',
            singular: 'cliente',
            description: 'Consultá y actualizá los datos de tus clientes.',
            icon: '♙',
            fields: [
                { name: 'nombre', label: 'Nombre', required: true, maxLength: 100 },
                { name: 'apellido', label: 'Apellido', required: true, maxLength: 100 },
                { name: 'email', label: 'Email', type: 'email', required: true, maxLength: 100 },
                { name: 'telefono', label: 'Teléfono', nullable: true, maxLength: 20 },
                { name: 'direccion', label: 'Dirección', type: 'textarea', nullable: true, maxLength: 255 }
            ]
        },
        ventas: {
            label: 'Ventas',
            singular: 'venta',
            description: 'Revisá las ventas registradas en el sistema.',
            icon: '＄',
            fields: [
                { name: 'cliente_id', label: 'ID de cliente', type: 'number', nullable: true, min: 1, step: '1' },
                { name: 'usuario_id', label: 'ID de usuario', type: 'number', nullable: true, min: 1, step: '1' },
                { name: 'fecha', label: 'Fecha', type: 'datetime-local', required: true },
                { name: 'total', label: 'Total', type: 'number', required: true, min: 0, step: '0.01' },
                { name: 'estado', label: 'Estado', required: true, maxLength: 50 }
            ]
        },
        detalle_ventas: {
            label: 'Detalle de ventas',
            singular: 'detalle de venta',
            description: 'Administrá los productos y cantidades asociados a cada venta.',
            icon: '≡',
            fields: [
                { name: 'venta_id', label: 'ID de venta', type: 'number', required: true, min: 1, step: '1' },
                { name: 'producto_id', label: 'ID de producto', type: 'number', required: true, min: 1, step: '1' },
                { name: 'cantidad', label: 'Cantidad', type: 'number', required: true, min: 1, step: '1' },
                { name: 'precio', label: 'Precio unitario', type: 'number', required: true, min: 0, step: '0.01' }
            ]
        },
        usuarios: {
            label: 'Usuarios',
            singular: 'usuario',
            description: 'Administrá las cuentas habilitadas para ingresar.',
            icon: '♧',
            fields: [
                { name: 'nombre', label: 'Nombre', required: true, maxLength: 100 },
                { name: 'apellido', label: 'Apellido', required: true, maxLength: 100 },
                { name: 'email', label: 'Email', type: 'email', required: true, maxLength: 100 },
                { name: 'password', label: 'Contraseña', type: 'password', required: true, help: 'Dejala vacía al editar para conservar la contraseña actual.' },
                { name: 'tipo_usuario_id', label: 'ID de tipo de usuario', type: 'number', nullable: true, min: 1, step: '1' }
            ]
        },
        tipo_usuario: {
            label: 'Tipos de usuario',
            singular: 'tipo de usuario',
            description: 'Configurá los roles de acceso del sistema.',
            icon: '⚙',
            fields: [
                { name: 'roles', label: 'Rol', required: true, maxLength: 100 }
            ]
        }
    };

    var state = { entity: 'productos', records: [], user: null, editingId: null };
    var loginView = document.getElementById('login-view');
    var appView = document.getElementById('app-view');
    var nav = document.getElementById('entity-nav');
    var table = document.getElementById('records-table');
    var loading = document.getElementById('loading');
    var emptyState = document.getElementById('empty-state');
    var notice = document.getElementById('notice');
    var dialog = document.getElementById('record-dialog');
    var form = document.getElementById('record-form');
    var formFields = document.getElementById('form-fields');
    var loginError = document.getElementById('login-error');
    var formError = document.getElementById('form-error');
    var saveButton = document.getElementById('save-button');

    function apiRequest(path, options) {
        options = options || {};
        options.credentials = 'same-origin';
        options.headers = options.headers || {};
        if (options.body && typeof options.body !== 'string') {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }

        return fetch(config.apiBase + '/' + path, options).then(function (response) {
            return response.text().then(function (body) {
                var result;
                try {
                    result = JSON.parse(body);
                } catch (error) {
                    throw new Error('El servidor devolvió una respuesta no válida.');
                }

                if (!response.ok || result.success === false) {
                    var apiError = new Error(result.message || 'No se pudo completar la operación.');
                    apiError.status = response.status;
                    throw apiError;
                }
                return result;
            });
        });
    }

    function setLoginError(message) {
        loginError.textContent = message || '';
        loginError.hidden = !message;
    }

    function showLogin(message) {
        appView.hidden = true;
        loginView.hidden = false;
        setLoginError(message);
    }

    function showApp(user) {
        state.user = user || null;
        loginView.hidden = true;
        appView.hidden = false;
        if (state.user) {
            var displayName = [state.user.nombre, state.user.apellido].filter(Boolean).join(' ');
            document.getElementById('user-name').textContent = displayName || state.user.email || 'Usuario';
            document.getElementById('user-role').textContent = state.user.rol || 'Sesión activa';
            document.getElementById('user-avatar').textContent = (displayName || state.user.email || 'U').charAt(0).toUpperCase();
        }
    }

    function showNotice(message, isError) {
        notice.textContent = message;
        notice.classList.toggle('error', Boolean(isError));
        notice.hidden = !message;
    }

    function handleApiError(error) {
        if (error.status === 401) {
            showLogin('La sesión venció. Iniciá sesión nuevamente.');
        } else {
            showNotice(error.message, true);
        }
    }

    function renderNavigation() {
        nav.replaceChildren();
        Object.keys(entities).forEach(function (key) {
            var item = entities[key];
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'nav-link';
            button.dataset.entity = key;
            button.innerHTML = '<span class="nav-icon" aria-hidden="true">' + item.icon + '</span><span>' + item.label + '</span>';
            button.addEventListener('click', function () {
                state.entity = key;
                showNotice('');
                renderNavigation();
                updateSection();
                loadRecords();
            });
            nav.appendChild(button);
        });
    }

    function updateSection() {
        var entity = entities[state.entity];
        document.getElementById('page-title').textContent = entity.label;
        document.getElementById('section-title').textContent = 'Listado de ' + entity.label.toLowerCase();
        document.getElementById('section-description').textContent = entity.description;
        document.getElementById('create-label').textContent = 'Nuevo ' + entity.singular;
        Array.prototype.forEach.call(nav.querySelectorAll('.nav-link'), function (button) {
            var active = button.dataset.entity === state.entity;
            button.classList.toggle('active', active);
            button.setAttribute('aria-current', active ? 'page' : 'false');
        });
    }

    function renderTable() {
        var entity = entities[state.entity];
        var head = document.getElementById('table-head');
        var body = document.getElementById('table-body');
        head.replaceChildren();
        body.replaceChildren();

        ['ID'].concat(entity.fields.map(function (field) {
            return field.label;
        }), ['Acciones']).forEach(function (label) {
            var cell = document.createElement('th');
            cell.scope = 'col';
            cell.textContent = label;
            head.appendChild(cell);
        });

        state.records.forEach(function (record) {
            var row = document.createElement('tr');
            appendCell(row, record.id);
            entity.fields.forEach(function (field) {
                appendCell(row, formatValue(record[field.name], field));
            });

            var actions = document.createElement('td');
            var actionGroup = document.createElement('div');
            actionGroup.className = 'row-actions';
            actionGroup.appendChild(actionButton('Editar', function () {
                openForm(record);
            }));
            actionGroup.appendChild(actionButton('Eliminar', function () {
                deleteRecord(record);
            }, 'delete'));
            actions.appendChild(actionGroup);
            row.appendChild(actions);
            body.appendChild(row);
        });

        loading.hidden = true;
        table.hidden = state.records.length === 0;
        emptyState.hidden = state.records.length !== 0;
    }

    function appendCell(row, value) {
        var cell = document.createElement('td');
        cell.textContent = value === null || typeof value === 'undefined' || value === ''
            ? '—'
            : String(value);
        row.appendChild(cell);
    }

    function formatValue(value, field) {
        if (value === null || typeof value === 'undefined' || value === '') {
            return '—';
        }
        if (field.name === 'precio' || field.name === 'total') {
            var amount = Number(value);
            return Number.isFinite(amount)
                ? new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(amount)
                : value;
        }
        return value;
    }

    function actionButton(label, action, extraClass) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'row-action' + (extraClass ? ' ' + extraClass : '');
        button.textContent = label;
        button.addEventListener('click', action);
        return button;
    }

    function loadRecords() {
        loading.hidden = false;
        loading.textContent = 'Cargando registros…';
        table.hidden = true;
        emptyState.hidden = true;

        apiRequest('listar.php?entidad=' + encodeURIComponent(state.entity), { method: 'GET' })
            .then(function (response) {
                state.records = response.data;
                showApp(state.user);
                renderTable();
            })
            .catch(function (error) {
                loading.hidden = true;
                handleApiError(error);
                if (error.status !== 401) {
                    loading.textContent = 'No se pudo cargar el listado.';
                    loading.hidden = false;
                }
            });
    }

    function openForm(record) {
        state.editingId = record ? record.id : null;
        form.reset();
        formFields.replaceChildren();
        formError.hidden = true;
        document.getElementById('dialog-title').textContent = (record ? 'Editar ' : 'Nuevo ') + entities[state.entity].singular;
        entities[state.entity].fields.forEach(function (field) {
            var wrapper = document.createElement('div');
            wrapper.className = 'field' + (field.type === 'textarea' ? ' full-width' : '');

            var label = document.createElement('label');
            var input = field.type === 'textarea'
                ? document.createElement('textarea')
                : document.createElement('input');
            var inputId = 'field-' + field.name;
            label.htmlFor = inputId;
            label.textContent = field.label;
            input.id = inputId;
            input.name = field.name;
            input.required = Boolean(field.required && (!record || field.name !== 'password'));
            if (field.type !== 'textarea') {
                input.type = field.type || 'text';
            }
            if (field.maxLength) {
                input.maxLength = field.maxLength;
            }
            if (field.min !== undefined) {
                input.min = field.min;
            }
            if (field.step) {
                input.step = field.step;
            }
            if (field.name === 'password') {
                input.autocomplete = 'new-password';
                input.required = !record;
            }
            if (record && field.name !== 'password' && record[field.name] !== null && record[field.name] !== undefined) {
                input.value = field.type === 'datetime-local'
                    ? String(record[field.name]).replace(' ', 'T').slice(0, 16)
                    : record[field.name];
            }

            wrapper.appendChild(label);
            wrapper.appendChild(input);
            if (field.help) {
                var help = document.createElement('small');
                help.className = 'field-help';
                help.textContent = field.help;
                wrapper.appendChild(help);
            }
            formFields.appendChild(wrapper);
        });

        dialog.showModal();
        var firstInput = formFields.querySelector('input, textarea');
        if (firstInput) {
            firstInput.focus();
        }
    }

    function submitRecord(event) {
        event.preventDefault();
        formError.hidden = true;
        saveButton.disabled = true;
        var payload = {};
        var entity = entities[state.entity];

        entity.fields.forEach(function (field) {
            var input = form.elements[field.name];
            var value = input.value.trim();
            if (field.name === 'password' && state.editingId && value === '') {
                return;
            }
            if (value === '' && field.nullable) {
                payload[field.name] = null;
            } else if (value !== '') {
                payload[field.name] = value;
            }
        });

        var method = state.editingId ? 'PATCH' : 'POST';
        var url = 'crear.php?entidad=' + encodeURIComponent(state.entity);
        if (state.editingId) {
            url = 'editar.php?entidad=' + encodeURIComponent(state.entity);
            url += '&id=' + encodeURIComponent(state.editingId);
        }

        apiRequest(url, { method: method, body: payload })
            .then(function (response) {
                dialog.close();
                showNotice(response.message || 'Cambios guardados correctamente.');
                loadRecords();
            })
            .catch(function (error) {
                if (error.status === 401) {
                    dialog.close();
                    showLogin('La sesión venció. Iniciá sesión nuevamente.');
                } else {
                    formError.textContent = error.message;
                    formError.hidden = false;
                }
            })
            .then(function () {
                saveButton.disabled = false;
            });
    }

    function deleteRecord(record) {
        if (!window.confirm('¿Eliminar este registro? Esta acción no se puede deshacer.')) {
            return;
        }
        var url = 'eliminar.php?entidad=' + encodeURIComponent(state.entity) + '&id=' + encodeURIComponent(record.id);
        apiRequest(url, { method: 'DELETE' })
            .then(function (response) {
                showNotice(response.message || 'Registro eliminado.');
                loadRecords();
            })
            .catch(handleApiError);
    }

    document.getElementById('login-form').addEventListener('submit', function (event) {
        event.preventDefault();
        setLoginError('');
        var button = event.currentTarget.querySelector('button[type="submit"]');
        button.disabled = true;
        apiRequest('index.php', {
            method: 'POST',
            body: {
                email: document.getElementById('login-email').value.trim(),
                password: document.getElementById('login-password').value
            }
        })
            .then(function (response) {
                document.getElementById('login-password').value = '';
                showApp(response.usuario);
                updateSection();
                loadRecords();
            })
            .catch(function (error) {
                setLoginError(error.message);
            })
            .then(function () {
                button.disabled = false;
            });
    });

    document.getElementById('create-button').addEventListener('click', function () {
        openForm(null);
    });
    document.getElementById('refresh-button').addEventListener('click', function () {
        showNotice('');
        loadRecords();
    });
    document.getElementById('close-dialog').addEventListener('click', function () {
        dialog.close();
    });
    document.getElementById('cancel-button').addEventListener('click', function () {
        dialog.close();
    });
    document.getElementById('logout-button').addEventListener('click', function () {
        apiRequest('logout.php', { method: 'POST' })
            .then(function () {
                state.user = null;
                showLogin('');
            })
            .catch(handleApiError);
    });
    form.addEventListener('submit', submitRecord);

    renderNavigation();
    updateSection();
    apiRequest('listar.php?entidad=productos', { method: 'GET' })
        .then(function (response) {
            state.records = response.data;
            showApp(null);
            renderTable();
        })
        .catch(function (error) {
            if (error.status === 401) {
                showLogin('');
                return;
            }
            showLogin('No se pudo verificar la conexión con el backend: ' + error.message);
        });
}());

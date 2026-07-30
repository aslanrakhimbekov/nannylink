@extends('layouts.app')

@section('content')
<div class="flex-1 flex flex-col pb-12">
    <!-- Header -->
    <div class="bg-safety-600 text-white px-6 py-6 rounded-b-3xl shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <span class="text-xs uppercase tracking-wider font-semibold opacity-75">Кабинет Няни</span>
                <h1 id="nanny-name" class="text-2xl font-bold">Загрузка...</h1>
            </div>
            <button onclick="logout()" class="bg-white/20 hover:bg-white/30 text-white rounded-xl py-2 px-3 text-xs font-semibold touch-target transition-all">
                Выйти
            </button>
        </div>

        <!-- Balance and Verification status card -->
        <div class="bg-white/10 rounded-2xl p-4 mt-4 flex justify-between items-center text-sm border border-white/10">
            <div>
                <span class="block text-xs opacity-75">Кошелек</span>
                <span class="text-xl font-bold" id="coin-balance">0</span> <span class="text-xs opacity-90">коинов (KZT)</span>
            </div>
            <div class="text-right">
                <span class="block text-xs opacity-75">Статус профиля</span>
                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold mt-1" id="verification-badge">
                    Загрузка...
                </span>
            </div>
        </div>
    </div>

    <!-- Main Section Tabs -->
    <div class="px-6 mt-6">
        <div class="border-b border-slate-200">
            <nav class="flex space-x-6" aria-label="Tabs">
                <button onclick="switchTab('search')" id="tab-btn-search" class="border-brand-500 text-brand-600 border-b-2 py-3 px-1 text-sm font-medium touch-target">
                    Поиск заказов
                </button>
                <button onclick="switchTab('verify')" id="tab-btn-verify" class="border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2 py-3 px-1 text-sm font-medium touch-target">
                    Проверка документов
                </button>
            </nav>
        </div>

        <!-- Search Tab -->
        <div id="tab-search" class="mt-6 space-y-4">
            <!-- Radius search control -->
            <div class="flex items-center space-x-3 bg-slate-50 p-3 rounded-2xl border">
                <label for="radius" class="text-xs font-semibold text-slate-500 w-1/3">Радиус поиска</label>
                <select id="radius" onchange="fetchNearbyOrders()"
                        class="flex-1 rounded-xl border-slate-200 py-1.5 px-3 text-sm focus:ring-brand-500">
                    <option value="1">В пределах 1 км</option>
                    <option value="3">В пределах 3 км</option>
                    <option value="5" selected>В пределах 5 км</option>
                    <option value="10">В пределах 10 км</option>
                </select>
            </div>

            <!-- Orders nearby list -->
            <div id="orders-nearby-container" class="space-y-4">
                <!-- Skeletons -->
                <div class="animate-pulse space-y-4">
                    <div class="h-28 bg-slate-100 rounded-2xl"></div>
                </div>
            </div>
        </div>

        <!-- Verify Tab -->
        <div id="tab-verify" class="hidden mt-6 space-y-6">
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
                <h3 class="text-sm font-bold text-slate-800 mb-2">Загрузить eGov справку</h3>
                <p class="text-xs text-slate-500 mb-4">
                    Загрузите медицинскую справку или справку о несудимости в формате PDF. Система автоматически извлечет ссылку для модератора.
                </p>

                <form id="doc-upload-form" onsubmit="uploadDocument(event)" class="space-y-4">
                    <div>
                        <label for="doc-type" class="block text-xs font-semibold text-slate-600">Тип документа</label>
                        <select id="doc-type" required
                                class="mt-1 block w-full rounded-xl border-slate-300 py-2 px-3 text-sm focus:ring-brand-500 ring-1 ring-inset ring-slate-300">
                            <option value="criminal_record">Справка о несудимости (criminal_record)</option>
                            <option value="medical_clearance">Медицинская справка (medical_clearance)</option>
                        </select>
                    </div>

                    <div>
                        <label for="doc-file" class="block text-xs font-semibold text-slate-600">PDF-файл</label>
                        <input id="doc-file" type="file" accept="application/pdf" required
                               class="mt-1 block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                    </div>

                    <div id="upload-error" class="hidden text-xs text-red-500 bg-red-50 p-3 rounded-xl border border-red-200"></div>
                    <div id="upload-success" class="hidden text-xs text-emerald-600 bg-emerald-50 p-3 rounded-xl border border-emerald-200"></div>

                    <button type="submit" 
                            class="w-full bg-brand-500 hover:bg-brand-600 text-white rounded-xl py-2.5 text-xs font-semibold transition-all touch-target shadow-sm">
                        Отправить документ
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let nannyUser = null;

    document.addEventListener('DOMContentLoaded', () => {
        nannyUser = JSON.parse(localStorage.getItem('user'));
        if (!nannyUser || nannyUser.role !== 'nanny') {
            window.location.href = '/login';
            return;
        }

        renderNannyDetails();
        fetchNearbyOrders();
    });

    function renderNannyDetails() {
        document.getElementById('nanny-name').innerText = nannyUser.profile.first_name || 'Няня';
        document.getElementById('coin-balance').innerText = nannyUser.profile.balance_coins;

        const badge = document.getElementById('verification-badge');
        if (nannyUser.profile.is_verified) {
            badge.innerText = 'Проверен';
            badge.className = 'inline-flex items-center rounded-md bg-emerald-500 text-white text-xs font-semibold mt-1 px-2 py-0.5';
        } else {
            badge.innerText = 'Не проверен';
            badge.className = 'inline-flex items-center rounded-md bg-amber-500 text-white text-xs font-semibold mt-1 px-2 py-0.5';
        }
    }

    function switchTab(tab) {
        const searchTab = document.getElementById('tab-search');
        const verifyTab = document.getElementById('tab-verify');
        const searchBtn = document.getElementById('tab-btn-search');
        const verifyBtn = document.getElementById('tab-btn-verify');

        if (tab === 'search') {
            searchTab.classList.remove('hidden');
            verifyTab.classList.add('hidden');
            searchBtn.className = 'border-brand-500 text-brand-600 border-b-2 py-3 px-1 text-sm font-medium touch-target';
            verifyBtn.className = 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2 py-3 px-1 text-sm font-medium touch-target';
        } else {
            searchTab.classList.add('hidden');
            verifyTab.classList.remove('hidden');
            verifyBtn.className = 'border-brand-500 text-brand-600 border-b-2 py-3 px-1 text-sm font-medium touch-target';
            searchBtn.className = 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2 py-3 px-1 text-sm font-medium touch-target';
        }
    }

    async function fetchNearbyOrders() {
        const container = document.getElementById('orders-nearby-container');
        const radius = document.getElementById('radius').value;

        // Use profile coordinates
        const lat = nannyUser.profile.latitude || 43.238949;
        const lng = nannyUser.profile.longitude || 76.889709;

        try {
            const orders = await apiRequest(`/api/v1/orders/nearby?latitude=${lat}&longitude=${lng}&radius_km=${radius}`);

            if (!orders || orders.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12 text-slate-500">
                        <span class="block text-3xl mb-2">🔍</span>
                        Нет активных заказов в этом радиусе.
                    </div>`;
                return;
            }

            container.innerHTML = orders.map(order => {
                const distanceText = order.distance_km !== undefined ? ` (~${parseFloat(order.distance_km).toFixed(1)} км)` : '';
                
                // Construct check constraints
                let buttonHtml = '';
                if (!nannyUser.profile.is_verified) {
                    buttonHtml = `
                        <button disabled class="w-full bg-slate-300 text-slate-500 rounded-xl py-2.5 text-xs font-semibold cursor-not-allowed">
                            Требуется верификация документов
                        </button>`;
                } else if (nannyUser.profile.balance_coins < 500) {
                    buttonHtml = `
                        <button disabled class="w-full bg-slate-300 text-slate-500 rounded-xl py-2.5 text-xs font-semibold cursor-not-allowed">
                            Недостаточно коинов (требуется 500)
                        </button>`;
                } else {
                    buttonHtml = `
                        <button onclick="respondToOrder(${order.id}, this)" 
                                class="w-full bg-brand-500 hover:bg-brand-600 text-white rounded-xl py-2.5 text-xs font-semibold touch-target transition-all shadow-sm">
                            Откликнуться (500 коинов)
                        </button>`;
                }

                return `
                    <div class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm space-y-3" id="order-card-${order.id}">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-bold text-slate-800 text-sm">${order.title}</h3>
                                <span class="text-[10px] text-slate-500 font-semibold block mt-0.5">${order.address_string} ${distanceText}</span>
                            </div>
                            <span class="text-brand-600 font-bold text-sm">
                                ${order.budget} KZT <span class="text-[10px] text-slate-500 font-normal">/${order.payment_type === 'hourly' ? 'час' : 'зад'}</span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 line-clamp-2">${order.description}</p>
                        
                        <div class="text-[10px] text-slate-500 flex space-x-4 border-t pt-2">
                            <span>Возраст: <strong>${order.child_age}</strong></span>
                            <span>Старт: <strong>${new Date(order.date_start).toLocaleDateString()}</strong></span>
                        </div>

                        <div id="respond-container-${order.id}" class="pt-2">
                            ${buttonHtml}
                        </div>
                    </div>`;
            }).join('');

        } catch (err) {
            container.innerHTML = '<div class="text-red-500 text-xs text-center py-6">Ошибка поиска ближайших заказов.</div>';
        }
    }

    async function respondToOrder(orderId, buttonEl) {
        if (!confirm('Вы уверены, что хотите откликнуться? С вашего баланса будет списано 500 коинов.')) {
            return;
        }

        const container = document.getElementById(`respond-container-${orderId}`);
        container.innerHTML = '<div class="text-center text-xs text-slate-500 py-2">Списание коинов и создание отклика...</div>';

        try {
            const response = await apiRequest(`/api/v1/orders/${orderId}/respond`, 'POST');
            
            if (response && response.parent_phone) {
                // OPTIMISTIC UI: Deduct 500 coins locally
                nannyUser.profile.balance_coins -= 500;
                localStorage.setItem('user', JSON.stringify(nannyUser));
                renderNannyDetails();

                // Show Parent Contact Information in Card
                container.innerHTML = `
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 space-y-2 mt-2">
                        <span class="block text-emerald-800 text-[10px] font-bold uppercase tracking-wider">Контакты родителя получены!</span>
                        <div class="text-xs text-slate-700 space-y-1">
                            <div>Имя: <strong class="text-slate-900">${response.parent_name}</strong></div>
                            <div>Тел: <strong class="text-slate-900">${response.parent_phone}</strong></div>
                            <div>Telegram: <strong class="text-slate-900">${response.parent_telegram_username || 'нет'}</strong></div>
                        </div>
                    </div>`;
            }
        } catch (err) {
            alert(err.data?.message || 'Не удалось отправить отклик');
            fetchNearbyOrders(); // reload
        }
    }

    async function uploadDocument(event) {
        event.preventDefault();
        const errorDiv = document.getElementById('upload-error');
        const successDiv = document.getElementById('upload-success');
        errorDiv.classList.add('hidden');
        successDiv.classList.add('hidden');

        const type = document.getElementById('doc-type').value;
        const fileInput = document.getElementById('doc-file');
        const file = fileInput.files[0];

        const formData = new FormData();
        formData.append('type', type);
        formData.append('file', file);

        const token = localStorage.getItem('auth_token');
        const lang = localStorage.getItem('language') || 'ru';

        try {
            const response = await fetch('/api/v1/nanny/documents', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                    'Accept-Language': lang
                },
                body: formData
            });

            const result = await response.json();
            if (!response.ok) {
                throw { data: result };
            }

            successDiv.classList.remove('hidden');
            successDiv.innerText = 'Документ успешно загружен! Модератор проверит его статус. Посмотрите логи бэкенда для автоматических проверок.';
            fileInput.value = '';
        } catch (err) {
            errorDiv.classList.remove('hidden');
            if (err.data && err.data.errors && err.data.errors.file) {
                errorDiv.innerText = err.data.errors.file[0];
            } else {
                errorDiv.innerText = err.data?.message || 'Не удалось загрузить документ';
            }
        }
    }

    function logout() {
        localStorage.clear();
        window.location.href = '/login';
    }
</script>
@endsection

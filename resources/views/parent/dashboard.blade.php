@extends('layouts.app')

@section('content')
<div class="flex-1 flex flex-col pb-12">
    <!-- Header -->
    <div class="bg-brand-500 text-white px-6 py-6 rounded-b-3xl shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <span class="text-xs uppercase tracking-wider font-semibold opacity-75">Кабинет Родителя</span>
                <h1 id="parent-name" class="text-2xl font-bold">Загрузка...</h1>
            </div>
            <button onclick="logout()" class="bg-white/20 hover:bg-white/30 text-white rounded-xl py-2 px-3 text-xs font-semibold touch-target transition-all">
                Выйти
            </button>
        </div>
    </div>

    <!-- Toggle forms and lists -->
    <div class="px-6 mt-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-slate-800">Размещенные заказы</h2>
            <button onclick="toggleOrderForm()" id="btn-toggle-form"
                    class="bg-brand-500 hover:bg-brand-600 text-white rounded-xl px-4 py-2 text-xs font-semibold touch-target transition-all shadow-sm">
                + Создать заказ
            </button>
        </div>

        <!-- Create Order Form -->
        <div id="order-form-container" class="hidden bg-slate-50 border border-slate-200 rounded-2xl p-4 mb-6 transition-all duration-300">
            <h3 class="text-sm font-bold text-slate-700 mb-3 border-b border-slate-200 pb-2">Новое задание</h3>
            <form id="order-form" onsubmit="createOrder(event)" class="space-y-4">
                <div>
                    <label for="title" class="block text-xs font-semibold text-slate-600">Название задания</label>
                    <input id="title" type="text" placeholder="Ищу няню на выходные" required
                           class="mt-1 block w-full rounded-xl border-slate-300 py-2 px-3 text-sm focus:ring-brand-500 ring-1 ring-inset ring-slate-300">
                </div>

                <div>
                    <label for="description" class="block text-xs font-semibold text-slate-600">Описание требований</label>
                    <textarea id="description" rows="2" placeholder="Заботы о ребенке 5 лет..." required
                              class="mt-1 block w-full rounded-xl border-slate-300 py-2 px-3 text-sm focus:ring-brand-500 ring-1 ring-inset ring-slate-300"></textarea>
                </div>

                <div>
                    <label for="address_string" class="block text-xs font-semibold text-slate-600">Адрес</label>
                    <input id="address_string" type="text" placeholder="Бостандыкский район, Аль-Фараби" required
                           class="mt-1 block w-full rounded-xl border-slate-300 py-2 px-3 text-sm focus:ring-brand-500 ring-1 ring-inset ring-slate-300">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="lat" class="block text-xs font-semibold text-slate-600">Широта (Lat)</label>
                        <input id="lat" type="number" step="0.000001" value="43.238949" required
                               class="mt-1 block w-full rounded-xl border-slate-300 py-2 px-3 text-sm focus:ring-brand-500 ring-1 ring-inset ring-slate-300">
                    </div>
                    <div>
                        <label for="lng" class="block text-xs font-semibold text-slate-600">Долгота (Lng)</label>
                        <input id="lng" type="number" step="0.000001" value="76.889709" required
                               class="mt-1 block w-full rounded-xl border-slate-300 py-2 px-3 text-sm focus:ring-brand-500 ring-1 ring-inset ring-slate-300">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label for="child_age" class="block text-xs font-semibold text-slate-600">Возраст ребенка</label>
                        <input id="child_age" type="number" value="3" min="0" required
                               class="mt-1 block w-full rounded-xl border-slate-300 py-2 px-3 text-sm focus:ring-brand-500 ring-1 ring-inset ring-slate-300">
                    </div>
                    <div>
                        <label for="payment_type" class="block text-xs font-semibold text-slate-600">Тип оплаты</label>
                        <select id="payment_type" required
                                class="mt-1 block w-full rounded-xl border-slate-300 py-2 px-3 text-sm focus:ring-brand-500 ring-1 ring-inset ring-slate-300">
                            <option value="hourly">Почасовая</option>
                            <option value="fixed">Фиксированная</option>
                        </select>
                    </div>
                    <div>
                        <label for="budget" class="block text-xs font-semibold text-slate-600">Бюджет (KZT)</label>
                        <input id="budget" type="number" value="2000" min="0" required
                               class="mt-1 block w-full rounded-xl border-slate-300 py-2 px-3 text-sm focus:ring-brand-500 ring-1 ring-inset ring-slate-300">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="date_start" class="block text-xs font-semibold text-slate-600">Начало</label>
                        <input id="date_start" type="datetime-local" required
                               class="mt-1 block w-full rounded-xl border-slate-300 py-2 px-3 text-sm focus:ring-brand-500 ring-1 ring-inset ring-slate-300">
                    </div>
                    <div>
                        <label for="date_end" class="block text-xs font-semibold text-slate-600">Окончание</label>
                        <input id="date_end" type="datetime-local" required
                               class="mt-1 block w-full rounded-xl border-slate-300 py-2 px-3 text-sm focus:ring-brand-500 ring-1 ring-inset ring-slate-300">
                    </div>
                </div>

                <div id="form-error" class="hidden text-xs text-red-500 bg-red-50 p-3 rounded-xl border border-red-200"></div>

                <div class="flex space-x-3 pt-2">
                    <button type="submit" 
                            class="flex-1 bg-safety-600 hover:bg-safety-700 text-white rounded-xl py-2.5 text-xs font-semibold transition-all">
                        Разместить заказ
                    </button>
                    <button type="button" onclick="toggleOrderForm()" 
                            class="bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl py-2.5 px-4 text-xs font-semibold transition-all">
                        Отмена
                    </button>
                </div>
            </form>
        </div>

        <!-- Orders List Container -->
        <div id="orders-container" class="space-y-4">
            <!-- Skeleton Loader -->
            <div class="animate-pulse space-y-4">
                <div class="h-24 bg-slate-100 rounded-2xl"></div>
                <div class="h-24 bg-slate-100 rounded-2xl"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dialog for viewing Responses -->
<div id="responses-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl relative max-h-[80vh] overflow-y-auto">
        <button onclick="closeResponsesModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-lg font-bold">×</button>
        <h3 id="modal-title" class="text-base font-bold text-slate-800 mb-4 border-b pb-2">Отклики нянь</h3>
        <div id="modal-content" class="space-y-4">
            <!-- Responses will be rendered here -->
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const user = JSON.parse(localStorage.getItem('user'));
        if (!user || user.role !== 'parent') {
            window.location.href = '/login';
            return;
        }

        document.getElementById('parent-name').innerText = user.profile.first_name || 'Родитель';
        
        // Preset start/end dates
        const now = new Date();
        now.setMinutes(0);
        document.getElementById('date_start').value = new Date(now.getTime() + 3600000 * 2).toISOString().slice(0, 16);
        document.getElementById('date_end').value = new Date(now.getTime() + 3600000 * 6).toISOString().slice(0, 16);

        fetchOrders();
    });

    function toggleOrderForm() {
        const container = document.getElementById('order-form-container');
        const btn = document.getElementById('btn-toggle-form');
        
        if (container.classList.contains('hidden')) {
            container.classList.remove('hidden');
            btn.innerText = 'Свернуть';
        } else {
            container.classList.add('hidden');
            btn.innerText = '+ Создать заказ';
        }
    }

    async function fetchOrders() {
        const container = document.getElementById('orders-container');
        try {
            const orders = await apiRequest('/api/v1/orders');
            
            if (!orders || orders.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12 text-slate-500">
                        <span class="block text-3xl mb-2">📭</span>
                        У вас пока нет размещенных заказов.
                    </div>`;
                return;
            }

            container.innerHTML = orders.map(order => {
                let statusColor = 'bg-blue-100 text-blue-800';
                if (order.status === 'matched') statusColor = 'bg-emerald-100 text-emerald-800';
                if (order.status === 'completed') statusColor = 'bg-slate-100 text-slate-800';
                if (order.status === 'cancelled') statusColor = 'bg-red-100 text-red-800';

                return `
                    <div class="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm hover:shadow-md transition-all relative">
                        <span class="absolute top-4 right-4 inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${statusColor}">
                            ${order.status}
                        </span>
                        <h3 class="font-bold text-slate-800 text-sm max-w-[200px] truncate">${order.title}</h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-[280px] truncate">${order.description}</p>
                        <div class="mt-4 flex items-center justify-between text-xs text-slate-600 border-t border-slate-100 pt-3">
                            <div>
                                <span class="font-semibold text-brand-600">${order.budget} KZT</span>
                                <span class="opacity-75"> / ${order.payment_type === 'hourly' ? 'час' : 'задание'}</span>
                            </div>
                            <button onclick="viewResponses(${order.id}, '${order.title.replace(/'/g, "\\'")}')" 
                                    class="text-brand-600 hover:text-brand-700 font-semibold flex items-center gap-1 touch-target">
                                Отклики (${order.responses_count || 0}) ➜
                            </button>
                        </div>
                    </div>`;
            }).join('');

        } catch (err) {
            container.innerHTML = '<div class="text-red-500 text-sm text-center py-6">Ошибка загрузки заказов.</div>';
        }
    }

    async function createOrder(event) {
        event.preventDefault();
        const errorDiv = document.getElementById('form-error');
        errorDiv.classList.add('hidden');

        const title = document.getElementById('title').value.trim();
        const description = document.getElementById('description').value.trim();
        const address_string = document.getElementById('address_string').value.trim();
        const latitude = parseFloat(document.getElementById('lat').value);
        const longitude = parseFloat(document.getElementById('lng').value);
        const child_age = parseInt(document.getElementById('child_age').value);
        const date_start = new Date(document.getElementById('date_start').value).toISOString();
        const date_end = new Date(document.getElementById('date_end').value).toISOString();
        const payment_type = document.getElementById('payment_type').value;
        const budget = parseInt(document.getElementById('budget').value);

        const data = { title, description, address_string, latitude, longitude, child_age, date_start, date_end, payment_type, budget };

        try {
            const response = await apiRequest('/api/v1/orders', 'POST', data);
            if (response) {
                document.getElementById('order-form').reset();
                toggleOrderForm();
                fetchOrders();
            }
        } catch (err) {
            errorDiv.classList.remove('hidden');
            if (err.data && err.data.errors) {
                errorDiv.innerText = Object.values(err.data.errors)[0][0];
            } else {
                errorDiv.innerText = err.data?.message || 'Ошибка добавления заказа';
            }
        }
    }

    async function viewResponses(orderId, orderTitle) {
        const modal = document.getElementById('responses-modal');
        const content = document.getElementById('modal-content');
        document.getElementById('modal-title').innerText = `Отклики: "${orderTitle}"`;
        
        modal.classList.remove('hidden');
        content.innerHTML = '<div class="text-center py-6 text-slate-500">Загрузка откликов...</div>';

        try {
            const order = await apiRequest(`/api/v1/orders/${orderId}`);
            const responses = order.responses || [];

            if (responses.length === 0) {
                content.innerHTML = '<div class="text-center py-6 text-slate-500">Пока никто не откликнулся.</div>';
                return;
            }

            content.innerHTML = responses.map(resp => {
                const nanny = resp.nanny;
                const profile = nanny.profile;
                const verificationBadge = profile.is_verified 
                    ? '<span class="bg-emerald-100 text-emerald-800 text-[10px] font-semibold px-2 py-0.5 rounded ml-2">Проверена</span>' 
                    : '<span class="bg-slate-100 text-slate-800 text-[10px] font-semibold px-2 py-0.5 rounded ml-2">В обработке</span>';

                return `
                    <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 space-y-2">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-bold text-slate-800 text-sm">
                                    ${profile.first_name} ${profile.last_name}
                                    ${verificationBadge}
                                </h4>
                                <p class="text-xs text-slate-500 mt-0.5">Опыт: ${profile.experience_years} лет | Ставка: ${profile.hourly_rate} KZT/ч</p>
                            </div>
                        </div>
                        <p class="text-xs text-slate-600 italic bg-white p-2 rounded-lg border border-slate-100">${profile.bio || 'Нет описания'}</p>
                        <div class="pt-2 text-xs text-slate-600 border-t flex justify-between items-center">
                            <span>Тел: <strong class="text-slate-800">${nanny.phone}</strong></span>
                            <span>Телеграм: <strong class="text-slate-800">${nanny.telegram_id ? '@' + nanny.telegram_id : 'нет'}</strong></span>
                        </div>
                    </div>`;
            }).join('');

        } catch (err) {
            content.innerHTML = '<div class="text-red-500 text-sm text-center">Не удалось загрузить отклики.</div>';
        }
    }

    function closeResponsesModal() {
        document.getElementById('responses-modal').classList.add('hidden');
    }

    function logout() {
        localStorage.clear();
        window.location.href = '/login';
    }
</script>
@endsection

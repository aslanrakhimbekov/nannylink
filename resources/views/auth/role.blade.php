@extends('layouts.app')

@section('content')
<div class="flex-1 flex flex-col justify-center px-6 py-12">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
        <h2 class="text-center text-2xl font-bold tracking-tight text-slate-900">
            Завершение регистрации
        </h2>
        <p class="mt-2 text-center text-sm text-slate-600">
            Пожалуйста, укажите ваши данные для продолжения
        </p>
    </div>

    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
        <form class="space-y-6" id="form-profile" onsubmit="submitProfile(event)">
            <div>
                <label for="first_name" class="block text-sm font-medium leading-6 text-slate-700">Имя</label>
                <div class="mt-2">
                    <input id="first_name" name="first_name" type="text" required 
                           class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500 text-base touch-target">
                </div>
            </div>

            <div>
                <label for="last_name" class="block text-sm font-medium leading-6 text-slate-700">Фамилия</label>
                <div class="mt-2">
                    <input id="last_name" name="last_name" type="text" required 
                           class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500 text-base touch-target">
                </div>
            </div>

            <div>
                <label for="role" class="block text-sm font-medium leading-6 text-slate-700">Роль в системе</label>
                <div class="mt-2">
                    <select id="role" name="role" onchange="toggleNannyFields(this.value)"
                            class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500 text-base touch-target">
                        <option value="parent">Родитель (Ищу няню)</option>
                        <option value="nanny">Няня (Ищу работу)</option>
                    </select>
                </div>
            </div>

            <!-- Nanny Extra Fields -->
            <div id="nanny-fields" class="hidden space-y-6 border-t border-slate-100 pt-6">
                <div>
                    <label for="bio" class="block text-sm font-medium leading-6 text-slate-700">О себе</label>
                    <div class="mt-2">
                        <textarea id="bio" name="bio" rows="3" placeholder="Расскажите о своем опыте..."
                                  class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500 text-base"></textarea>
                    </div>
                </div>

                <div>
                    <label for="hourly_rate" class="block text-sm font-medium leading-6 text-slate-700">Почасовая ставка (KZT/час)</label>
                    <div class="mt-2">
                        <input id="hourly_rate" name="hourly_rate" type="number" min="0" value="1500"
                               class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500 text-base touch-target">
                    </div>
                </div>

                <div>
                    <label for="experience_years" class="block text-sm font-medium leading-6 text-slate-700">Опыт работы (лет)</label>
                    <div class="mt-2">
                        <input id="experience_years" name="experience_years" type="number" min="0" value="1"
                               class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-500 text-base touch-target">
                    </div>
                </div>
            </div>

            <!-- Coordinates simulation -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-2">Геолокация (для поиска)</span>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="latitude" class="block text-xs font-medium text-slate-500">Широта</label>
                        <input id="latitude" name="latitude" type="number" step="0.000001" value="43.238949" required
                               class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500 text-sm">
                    </div>
                    <div>
                        <label for="longitude" class="block text-xs font-medium text-slate-500">Долгота</label>
                        <input id="longitude" name="longitude" type="number" step="0.000001" value="76.889709" required
                               class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500 text-sm">
                    </div>
                </div>
                <button type="button" onclick="useMockCoordinates()" 
                        class="mt-3 text-xs text-brand-600 hover:text-brand-700 font-medium block">
                    Использовать центр Алматы
                </button>
            </div>

            <div id="submit-error" class="hidden text-sm text-red-500 bg-red-50 p-3 rounded-xl border border-red-200"></div>

            <div>
                <button type="submit" 
                        class="flex w-full justify-center rounded-xl bg-brand-500 py-3 px-4 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 touch-target transition-all duration-150">
                    Завершить регистрацию
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleNannyFields(role) {
        const fields = document.getElementById('nanny-fields');
        if (role === 'nanny') {
            fields.classList.remove('hidden');
        } else {
            fields.classList.add('hidden');
        }
    }

    function useMockCoordinates() {
        document.getElementById('latitude').value = "43.238949";
        document.getElementById('longitude').value = "76.889709";
    }

    async function submitProfile(event) {
        event.preventDefault();
        const errorDiv = document.getElementById('submit-error');
        errorDiv.classList.add('hidden');

        const role = document.getElementById('role').value;
        const first_name = document.getElementById('first_name').value.trim();
        const last_name = document.getElementById('last_name').value.trim();
        const latitude = parseFloat(document.getElementById('latitude').value);
        const longitude = parseFloat(document.getElementById('longitude').value);

        const data = { role, first_name, last_name, latitude, longitude };

        if (role === 'nanny') {
            data.bio = document.getElementById('bio').value.trim();
            data.hourly_rate = parseInt(document.getElementById('hourly_rate').value);
            data.experience_years = parseInt(document.getElementById('experience_years').value);
        }

        try {
            const response = await apiRequest('/api/v1/profile', 'POST', data);
            if (response && response.status === 'success') {
                localStorage.setItem('user', JSON.stringify(response.user));
                if (role === 'nanny') {
                    window.location.href = '/nanny';
                } else {
                    window.location.href = '/parent';
                }
            }
        } catch (err) {
            errorDiv.classList.remove('hidden');
            if (err.data && err.data.errors) {
                const firstError = Object.values(err.data.errors)[0][0];
                errorDiv.innerText = firstError;
            } else {
                errorDiv.innerText = err.data?.message || 'Ошибка обновления профиля';
            }
        }
    }
</script>
@endsection

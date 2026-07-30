<?php $__env->startSection('content'); ?>
<div class="flex-1 flex flex-col justify-center px-6 py-12">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
        <div class="flex justify-center text-brand-500 font-bold text-3xl tracking-tight">
            Nanny<span class="text-safety-600">Link</span>
        </div>
        <h2 class="mt-6 text-center text-xl font-medium tracking-tight text-slate-900">
            Вход в систему верификации
        </h2>
    </div>

    <!-- Phone step -->
    <div id="step-phone" class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
        <div class="space-y-6">
            <div>
                <label for="phone" class="block text-sm font-medium leading-6 text-slate-700">Номер телефона</label>
                <div class="mt-2">
                    <input id="phone" name="phone" type="tel" placeholder="+77XXXXXXXXX" required 
                           class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500 text-base touch-target">
                </div>
                <p class="mt-2 text-xs text-slate-500">Введите номер в международном формате: +77XXXXXXXXX</p>
            </div>

            <div id="phone-error" class="hidden text-sm text-red-500 bg-red-50 p-3 rounded-xl border border-red-200"></div>

            <div>
                <button type="button" id="btn-send-otp" onclick="sendOtp()"
                        class="flex w-full justify-center rounded-xl bg-brand-500 py-3 px-4 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 touch-target transition-all duration-150">
                    Получить код
                </button>
            </div>
        </div>
    </div>

    <!-- OTP Code step -->
    <div id="step-otp" class="hidden mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
        <div class="space-y-6">
            <div>
                <label for="otp-code" class="block text-sm font-medium leading-6 text-slate-700">Код подтверждения</label>
                <div class="mt-2">
                    <input id="otp-code" name="code" type="number" pattern="[0-9]*" inputmode="numeric" placeholder="1234" required 
                           class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500 text-base tracking-widest text-center font-semibold touch-target">
                </div>
                <p class="mt-2 text-xs text-slate-500">Код был отправлен на ваш номер (посмотрите логи бэкенда для тестирования)</p>
            </div>

            <div id="otp-error" class="hidden text-sm text-red-500 bg-red-50 p-3 rounded-xl border border-red-200"></div>

            <div>
                <button type="button" id="btn-verify-otp" onclick="verifyOtpCode()"
                        class="flex w-full justify-center rounded-xl bg-safety-600 py-3 px-4 text-sm font-semibold text-white shadow-sm hover:bg-safety-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-safety-600 touch-target transition-all duration-150">
                    Войти
                </button>
            </div>

            <div class="text-center">
                <button type="button" onclick="showPhoneStep()" class="text-xs text-brand-600 hover:text-brand-700 font-medium">
                    Изменить номер телефона
                </button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // If already logged in, redirect
        const token = localStorage.getItem('auth_token');
        if (token) {
            const user = JSON.parse(localStorage.getItem('user'));
            redirectUser(user);
        }
    });

    function showPhoneStep() {
        document.getElementById('step-phone').classList.remove('hidden');
        document.getElementById('step-otp').classList.add('hidden');
        document.getElementById('phone-error').classList.add('hidden');
    }

    async function sendOtp() {
        const phone = document.getElementById('phone').value.trim();
        const errorDiv = document.getElementById('phone-error');
        errorDiv.classList.add('hidden');

        try {
            const response = await apiRequest('/api/v1/auth/request-otp', 'POST', { phone });
            if (response && response.status === 'success') {
                document.getElementById('step-phone').classList.add('hidden');
                document.getElementById('step-otp').classList.remove('hidden');
            }
        } catch (err) {
            errorDiv.classList.remove('hidden');
            if (err.status === 429) {
                errorDiv.innerText = 'Слишком много запросов. Пожалуйста, попробуйте позже.';
            } else if (err.data && err.data.errors && err.data.errors.phone) {
                errorDiv.innerText = err.data.errors.phone[0];
            } else {
                errorDiv.innerText = err.data?.message || 'Произошла ошибка при отправке OTP';
            }
        }
    }

    async function verifyOtpCode() {
        const phone = document.getElementById('phone').value.trim();
        const code = document.getElementById('otp-code').value.trim();
        const errorDiv = document.getElementById('otp-error');
        errorDiv.classList.add('hidden');

        try {
            const response = await apiRequest('/api/v1/auth/verify-otp', 'POST', { phone, code });
            if (response && response.token) {
                localStorage.setItem('auth_token', response.token);
                localStorage.setItem('user', JSON.stringify(response.user));
                
                redirectUser(response.user);
            }
        } catch (err) {
            errorDiv.classList.remove('hidden');
            if (err.data && err.data.errors && err.data.errors.code) {
                errorDiv.innerText = err.data.errors.code[0];
            } else {
                errorDiv.innerText = err.data?.message || 'Неверный код верификации';
            }
        }
    }

    function redirectUser(user) {
        if (!user.profile || !user.profile.first_name) {
            // New user without profile data
            window.location.href = '/role-select';
        } else if (user.role === 'nanny') {
            window.location.href = '/nanny';
        } else {
            window.location.href = '/parent';
        }
    }
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/auth/login.blade.php ENDPATH**/ ?>
<!DOCTYPE html>
<html>
<head>
    <title>NannyLink Redirector</title>
    <script>
        const token = localStorage.getItem('auth_token');
        if (!token) {
            window.location.href = '/login';
        } else {
            const user = JSON.parse(localStorage.getItem('user'));
            if (!user || !user.profile || !user.profile.first_name) {
                window.location.href = '/role-select';
            } else if (user.role === 'nanny') {
                window.location.href = '/nanny';
            } else {
                window.location.href = '/parent';
            }
        }
    </script>
</head>
<body>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/welcome.blade.php ENDPATH**/ ?>
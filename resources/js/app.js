import './bootstrap';

const path = require('path');
// جعل الخادم يقرأ الملفات من مجلد الـ build
app.use(express.static(path.join(__dirname, '../frontend/dist')));

// أي طلب يأتي غير مسارات الـ API، قم بتحويله لصفحة الـ React الرئيسية
app.get('*', (req, res) => {
    res.sendFile(path.join(__dirname, '../frontend/dist', 'index.html'));
});

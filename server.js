const express = require('express');
const session = require('express-session');
const bcrypt = require('bcryptjs');
const fs = require('fs');
const path = require('path');
const cookieParser = require('cookie-parser');

const app = express();
const PORT = process.env.PORT || 3000;
const DB_FILE = path.join(__dirname, 'data', 'db.json');

// Simple JSON database
function loadDB() {
  try {
    if (fs.existsSync(DB_FILE)) {
      return JSON.parse(fs.readFileSync(DB_FILE, 'utf8'));
    }
  } catch (e) {}
  return { users: [], applications: [], reviews: [], nextUserId: 1, nextAppId: 1, nextReviewId: 1 };
}

function saveDB(db) {
  fs.mkdirSync(path.dirname(DB_FILE), { recursive: true });
  fs.writeFileSync(DB_FILE, JSON.stringify(db, null, 2));
}

let db = loadDB();

// Middleware
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(cookieParser());
app.use(session({
  secret: 'banketam-secret-key-2026',
  resave: false,
  saveUninitialized: false,
  cookie: { maxAge: 24 * 60 * 60 * 1000 }
}));
app.use(express.static(path.join(__dirname, 'public')));

const ADMIN_LOGIN = 'Admin26';
const ADMIN_PASSWORD = 'Demo20';

function requireAuth(req, res, next) {
  if (!req.session.userId && !req.session.isAdmin) {
    return res.status(401).json({ error: 'Требуется авторизация' });
  }
  next();
}

function requireAdmin(req, res, next) {
  if (!req.session.isAdmin) {
    return res.status(403).json({ error: 'Доступ запрещён' });
  }
  next();
}

// Register
app.post('/api/register', async (req, res) => {
  try {
    const { login, password, password2, full_name, phone, email } = req.body;
    const errors = {};

    if (!login) errors.login = 'Введите логин';
    else if (!/^[a-zA-Z0-9]{6,}$/.test(login)) errors.login = 'Логин: только латинские буквы и цифры, минимум 6 символов';

    if (!password) errors.password = 'Введите пароль';
    else if (password.length < 8) errors.password = 'Пароль должен быть не менее 8 символов';

    if (password !== password2) errors.password2 = 'Пароли не совпадают';
    if (!full_name) errors.full_name = 'Введите ФИО';
    if (!phone) errors.phone = 'Введите телефон';
    else if (!/^[\d\s\-\+\(\)]{10,}$/.test(phone)) errors.phone = 'Некорректный номер телефона';
    if (!email) errors.email = 'Введите e-mail';
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.email = 'Некорректный e-mail';

    if (!errors.login && db.users.find(u => u.login === login)) {
      errors.login = 'Такой логин уже занят';
    }

    if (Object.keys(errors).length > 0) return res.status(400).json({ errors });

    const hash = await bcrypt.hash(password, 10);
    db.users.push({
      id: db.nextUserId++,
      login, password: hash, full_name, phone, email,
      created_at: new Date().toISOString()
    });
    saveDB(db);
    res.json({ success: true, message: 'Регистрация успешна' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Ошибка сервера' });
  }
});

// Login
app.post('/api/login', async (req, res) => {
  try {
    const { login, password } = req.body;
    if (!login || !password) return res.status(400).json({ error: 'Заполните все поля' });

    if (login === ADMIN_LOGIN && password === ADMIN_PASSWORD) {
      req.session.userId = 0;
      req.session.isAdmin = true;
      req.session.login = ADMIN_LOGIN;
      return res.json({ success: true, isAdmin: true, redirect: '/admin.html' });
    }

    const user = db.users.find(u => u.login === login);
    if (!user || !(await bcrypt.compare(password, user.password))) {
      return res.status(401).json({ error: 'Неверный логин или пароль' });
    }

    req.session.userId = user.id;
    req.session.isAdmin = false;
    req.session.login = login;
    req.session.fullName = user.full_name;
    res.json({ success: true, isAdmin: false, redirect: '/cabinet.html' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Ошибка сервера' });
  }
});

app.post('/api/logout', (req, res) => {
  req.session.destroy();
  res.json({ success: true });
});

app.get('/api/me', (req, res) => {
  if (req.session.isAdmin) return res.json({ isAdmin: true, login: ADMIN_LOGIN });
  if (!req.session.userId) return res.json({ loggedIn: false });
  const user = db.users.find(u => u.id === req.session.userId);
  if (!user) return res.json({ loggedIn: false });
  res.json({
    loggedIn: true, isAdmin: false,
    user: { id: user.id, login: user.login, full_name: user.full_name, phone: user.phone, email: user.email }
  });
});

// Create application
app.post('/api/applications', requireAuth, (req, res) => {
  if (req.session.isAdmin) return res.status(403).json({ error: 'Администратор не может создавать заявки' });

  const { room_type, banquet_date, payment_method } = req.body;
  const rooms = ['зал', 'ресторан', 'летняя веранда', 'закрытая веранда'];
  const payments = ['Предоплата по QR-коду', 'Оплата картой МИР', 'Постоплата в офисе организации'];
  const errors = {};

  if (!room_type || !rooms.includes(room_type)) errors.room_type = 'Выберите помещение';
  if (!banquet_date) errors.banquet_date = 'Укажите дату';
  else if (!/^\d{2}\.\d{2}\.\d{4}$/.test(banquet_date)) errors.banquet_date = 'Дата в формате ДД.ММ.ГГГГ';
  else {
    const [d, m, y] = banquet_date.split('.').map(Number);
    const date = new Date(y, m - 1, d);
    if (date.getFullYear() !== y || date.getMonth() !== m - 1 || date.getDate() !== d) {
      errors.banquet_date = 'Некорректная дата';
    }
  }
  if (!payment_method || !payments.includes(payment_method)) errors.payment_method = 'Выберите способ оплаты';

  if (Object.keys(errors).length > 0) return res.status(400).json({ errors });

  const appItem = {
    id: db.nextAppId++,
    user_id: req.session.userId,
    room_type, banquet_date, payment_method,
    status: 'Новая',
    created_at: new Date().toISOString()
  };
  db.applications.push(appItem);
  saveDB(db);
  res.json({ success: true, id: appItem.id });
});

// My applications
app.get('/api/applications/my', requireAuth, (req, res) => {
  if (req.session.isAdmin) return res.status(403).json({ error: 'Доступ запрещён' });

  const apps = db.applications
    .filter(a => a.user_id === req.session.userId)
    .map(a => {
      const review = db.reviews.find(r => r.application_id === a.id);
      return { ...a, review_text: review ? review.review_text : null, review_date: review ? review.created_at : null };
    })
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

  res.json({ applications: apps });
});

// Add review
app.post('/api/reviews', requireAuth, (req, res) => {
  if (req.session.isAdmin) return res.status(403).json({ error: 'Доступ запрещён' });

  const { application_id, review_text } = req.body;
  if (!review_text || !review_text.trim()) return res.status(400).json({ error: 'Введите текст отзыва' });

  const appItem = db.applications.find(a => a.id === parseInt(application_id) && a.user_id === req.session.userId);
  if (!appItem) return res.status(404).json({ error: 'Заявка не найдена' });
  if (appItem.status === 'Новая') return res.status(400).json({ error: 'Отзыв можно оставить только после изменения статуса администратором' });
  if (db.reviews.find(r => r.application_id === appItem.id)) return res.status(400).json({ error: 'Отзыв уже оставлен' });

  db.reviews.push({
    id: db.nextReviewId++,
    application_id: appItem.id,
    user_id: req.session.userId,
    review_text: review_text.trim(),
    created_at: new Date().toISOString()
  });
  saveDB(db);
  res.json({ success: true });
});

// Admin list
app.get('/api/admin/applications', requireAdmin, (req, res) => {
  const status = req.query.status || '';
  const sort = ['id', 'created_at', 'banquet_date', 'status', 'room_type'].includes(req.query.sort) ? req.query.sort : 'created_at';
  const order = req.query.order === 'ASC' ? 'ASC' : 'DESC';
  const page = Math.max(1, parseInt(req.query.page) || 1);
  const perPage = 5;

  let list = db.applications.map(a => {
    const user = db.users.find(u => u.id === a.user_id) || {};
    return { ...a, full_name: user.full_name || '', login: user.login || '', phone: user.phone || '', email: user.email || '' };
  });

  if (['Новая', 'Банкет назначен', 'Банкет завершен'].includes(status)) {
    list = list.filter(a => a.status === status);
  }

  list.sort((a, b) => {
    let va = a[sort], vb = b[sort];
    if (sort === 'created_at' || sort === 'banquet_date') {
      va = new Date(va); vb = new Date(vb);
    }
    if (va < vb) return order === 'ASC' ? -1 : 1;
    if (va > vb) return order === 'ASC' ? 1 : -1;
    return 0;
  });

  const total = list.length;
  const totalPages = Math.max(1, Math.ceil(total / perPage));
  const offset = (page - 1) * perPage;
  const pageList = list.slice(offset, offset + perPage);

  res.json({ applications: pageList, total, totalPages, page });
});

// Admin update status
app.post('/api/admin/applications/:id/status', requireAdmin, (req, res) => {
  const id = parseInt(req.params.id);
  const { status } = req.body;
  const allowed = ['Новая', 'Банкет назначен', 'Банкет завершен'];
  if (!allowed.includes(status)) return res.status(400).json({ error: 'Некорректный статус' });

  const appItem = db.applications.find(a => a.id === id);
  if (!appItem) return res.status(404).json({ error: 'Заявка не найдена' });

  appItem.status = status;
  saveDB(db);
  res.json({ success: true, message: `Статус заявки #${id} изменён на «${status}»` });
});

app.listen(PORT, () => {
  console.log(`Банкетам.Нет запущен: http://localhost:${PORT}`);
});

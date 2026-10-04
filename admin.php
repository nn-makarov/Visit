<?php
// admin.php

require_once __DIR__ . '/config.php';

// ============ ЗАЩИТА ПАРОЛЕМ ============
session_start();

if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 28800)) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Проверяем, ввёл ли пользователь пароль
if (isset($_POST['password'])) {
    usleep(500000); // замедление перебора пароля
    $password = $_POST['password'];
    if (password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['login_time'] = time();
    } else {
        $error = '❌ Неверный пароль!';
    }
}

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="utf-8">
        <title>Вход в админку</title>
    </head>
    <body>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}
    
    
header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>📋 Панель заявок - AutoPlastic 3D</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #ff6b00 0%, #ff8c42 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #ff6b00;
        }
        
        .orders-container {
            padding: 30px;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }
        
        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #ff6b00;
        }
        
        .status-filter {
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            font-size: 1rem;
            cursor: pointer;
        }
        
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .order-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            border-color: #ff6b00;
        }
        
        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-id {
            font-weight: bold;
            font-size: 1.2rem;
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .order-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        
        .status-new {
            background: #ff6b00;
            color: white;
        }
        
        .status-processed {
            background: #28a745;
            color: white;
        }
        
        .order-content {
            padding: 20px;
        }
        
        .order-field {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .order-field:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .field-label {
            font-weight: 600;
            color: #666;
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .field-value {
            font-size: 1.1rem;
            color: #333;
        }
        
        .phone-link {
            color: #ff6b00;
            text-decoration: none;
            font-weight: bold;
        }
        
        .phone-link:hover {
            text-decoration: underline;
        }
        
        .order-actions {
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-call {
            background: #28a745;
            color: white;
        }
        
        .btn-call:hover {
            background: #218838;
        }
        
        .btn-process {
            background: #ff6b00;
            color: white;
        }
        
        .btn-process:hover {
            background: #e65c00;
        }
        
        .btn-view {
            background: #6f42c1;
            color: white;
        }
        
        .btn-view:hover {
            background: #5a32a3;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .file-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #6f42c1;
            text-decoration: none;
            font-weight: 600;
        }
        
        .file-link:hover {
            text-decoration: underline;
        }
        
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #333;
        }
        
        @media (max-width: 768px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .filters {
                flex-direction: column;
            }
            
            .search-input, .status-filter {
                width: 100%;
            }
        }
        
        /* Анимация появления */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .order-card {
            animation: fadeIn 0.5s ease forwards;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tools"></i> Панель управления заявками</h1>
            <p>AutoPlastic 3D - 3D моделирование и изготовление пластика</p>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Всего заявок</h3>
                <div class="number" id="total-orders">0</div>
            </div>
            <div class="stat-card">
                <h3>Новых</h3>
                <div class="number" id="new-orders">0</div>
            </div>
            <div class="stat-card">
                <h3>Обработанных</h3>
                <div class="number" id="processed-orders">0</div>
            </div>
            <div class="stat-card">
                <h3>Сегодня</h3>
                <div class="number" id="today-orders">0</div>
            </div>
        </div>
        
        <div class="orders-container">
            <div class="filters">
                <input type="text" id="search-input" class="search-input" placeholder="🔍 Поиск по имени, телефону или сообщению...">
                <select id="status-filter" class="status-filter">
                    <option value="all">Все статусы</option>
                    <option value="new">Только новые</option>
                    <option value="processed">Только обработанные</option>
                </select>
                <button onclick="refreshOrders()" class="action-btn" style="background: #17a2b8; color: white;">
                    <i class="fas fa-sync-alt"></i> Обновить
                </button>
                <button onclick="exportToCSV()" class="action-btn" style="background: #20c997; color: white;">
                    <i class="fas fa-file-export"></i> Экспорт в CSV
                </button>
            </div>
            
            <div class="orders-grid" id="orders-grid">
                <!-- Заявки будут загружены через JavaScript -->
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Заявок пока нет</h3>
                    <p>Отправьте первую заявку с главной страницы сайта</p>
                    <a href="/" class="action-btn btn-process" style="margin-top: 20px;">
                        <i class="fas fa-external-link-alt"></i> Перейти на сайт
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Функция загрузки заявок
        async function loadOrders() {
            try {
                const response = await fetch('get_orders.php');
                const orders = await response.json();
                
                const ordersGrid = document.getElementById('orders-grid');
                const searchInput = document.getElementById('search-input').value.toLowerCase();
                const statusFilter = document.getElementById('status-filter').value;
                
                // Статистика
                const today = new Date().toISOString().split('T')[0];
                let total = 0, newCount = 0, processedCount = 0, todayCount = 0;
                
                // Фильтрация
                const filteredOrders = orders.filter(order => {
                    total++;
                    if (order.status === 'new') newCount++;
                    if (order.status === 'processed') processedCount++;
                    if (order.timestamp.startsWith(today)) todayCount++;
                    
                    // Поиск
                    if (searchInput) {
                        const searchStr = (
                            order.name + ' ' + 
                            order.phone + ' ' + 
                            order.message + ' ' + 
                            order.id
                        ).toLowerCase();
                        if (!searchStr.includes(searchInput)) return false;
                    }
                    
                    // Фильтр по статусу
                    if (statusFilter !== 'all' && order.status !== statusFilter) {
                        return false;
                    }
                    
                    return true;
                });
                
                // Обновляем статистику
                document.getElementById('total-orders').textContent = total;
                document.getElementById('new-orders').textContent = newCount;
                document.getElementById('processed-orders').textContent = processedCount;
                document.getElementById('today-orders').textContent = todayCount;
                
                // Очищаем контейнер
                ordersGrid.innerHTML = '';
                
                // Если нет заявок
                if (filteredOrders.length === 0) {
                    ordersGrid.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>Заявки не найдены</h3>
                            <p>Попробуйте изменить параметры поиска</p>
                        </div>
                    `;
                    return;
                }
                
                // Рендерим заявки
                filteredOrders.forEach(order => {
                    const orderCard = document.createElement('div');
                    orderCard.className = 'order-card';
                    orderCard.innerHTML = `
                        <div class="order-header">
                            <div class="order-id">${order.id}</div>
                            <div class="order-status status-${order.status}">
                                ${order.status === 'new' ? 'НОВАЯ' : 'ОБРАБОТАНА'}
                            </div>
                        </div>
                        
                        <div class="order-content">
                            <div class="order-field">
                                <span class="field-label">📅 Дата и время</span>
                                <div class="field-value">${order.timestamp}</div>
                            </div>
                            
                            <div class="order-field">
                                <span class="field-label">👤 Имя</span>
                                <div class="field-value">${escapeHtml(order.name)}</div>
                            </div>
                            
                            <div class="order-field">
                                <span class="field-label">📞 Телефон</span>
                                <div class="field-value">
                                    <a href="tel:${escapeHtml(order.phone)}" class="phone-link">
                                        <i class="fas fa-phone"></i> ${escapeHtml(order.phone)}
                                    </a>
                                </div>
                            </div>
                            
                            <div class="order-field">
                                <span class="field-label">💬 Сообщение</span>
                                <div class="field-value">${escapeHtml(order.message || 'Нет сообщения')}</div>
                            </div>
                            
                            ${order.file ? `
                            <div class="order-field">
                                <span class="field-label">📎 Файл</span>
                                <div class="field-value">
                                    <a href="${order.file.path}" target="_blank" class="file-link">
                                        <i class="fas fa-paperclip"></i> ${escapeHtml(order.file.name)}
                                        (${formatFileSize(order.file.size)})
                                    </a>
                                </div>
                            </div>
                            ` : ''}
                            
                            <div class="order-field">
                                <span class="field-label">🌐 IP адрес</span>
                                <div class="field-value">${order.ip}</div>
                            </div>
                        </div>
                        
                        <div class="order-actions">
                            <a href="tel:${escapeHtml(order.phone)}" class="action-btn btn-call">
                                <i class="fas fa-phone"></i> Позвонить
                            </a>
                            
                            <button onclick="markAsProcessed('${order.id}')" class="action-btn btn-process">
                                <i class="fas fa-check"></i> Обработано
                            </button>
                            
                            <button onclick="deleteOrder('${order.id}')" class="action-btn btn-delete">
                                <i class="fas fa-trash"></i> Удалить
                            </button>
                        </div>
                    `;
                    
                    ordersGrid.appendChild(orderCard);
                });
                
            } catch (error) {
                console.error('Ошибка загрузки заявок:', error);
                document.getElementById('orders-grid').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Ошибка загрузки</h3>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }
        
        // Вспомогательные функции
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Действия с заявками
        async function markAsProcessed(orderId) {
            if (confirm('Отметить заявку как обработанную?')) {
                try {
                    const response = await fetch('update_order.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: orderId, status: 'processed'})
                    });
                    
                    if (response.ok) {
                        loadOrders();
                    }
                } catch (error) {
                    alert('Ошибка: ' + error.message);
                }
            }
        }
        
        async function deleteOrder(orderId) {
            if (confirm('Удалить эту заявку? Это действие нельзя отменить.')) {
                try {
                    const response = await fetch('delete_order.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: orderId})
                    });
                    
                    if (response.ok) {
                        loadOrders();
                    }
                } catch (error) {
                    alert('Ошибка: ' + error.message);
                }
            }
        }
        
        function refreshOrders() {
            loadOrders();
        }
        
        function exportToCSV() {
            alert('Функция экспорта будет добавлена позже');
            // Можно реализовать экспорт всех заявок в CSV
        }
        
        // Инициализация
        document.addEventListener('DOMContentLoaded', () => {
            loadOrders();
            
            // Поиск при вводе
            document.getElementById('search-input').addEventListener('input', loadOrders);
            document.getElementById('status-filter').addEventListener('change', loadOrders);
            
            // Автообновление каждые 30 секунд
            setInterval(loadOrders, 30000);
        });
    </script>
</body>
</html>
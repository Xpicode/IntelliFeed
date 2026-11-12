<?php
session_start();
require_once 'api/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId   = $_SESSION['user_id'];
$username = $_SESSION['username'];
$fullName = $_SESSION['full_name'] ?? $username;

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM saved_pets WHERE user_id = ?");
$stmt->execute([$userId]);
$petCount = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>IntelliFeed · Smart Pet Care</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #faf7f2;
            --bg-gradient: radial-gradient(circle at top right, #f5efe6, #f0e9e0);
            --text-primary: #2c3e4f;
            --text-secondary: #5d6d7e;
            --text-muted: #8a9aa8;
            --card-bg: rgba(255, 255, 255, 0.85);
            --card-border: rgba(210, 190, 170, 0.3);
            --shadow-color: rgba(0, 0, 0, 0.05);
            --hover-bg: #faf0e6;
            --input-bg: #ffffff;
            --input-border: #e2d9d0;
            --badge-bg: #f2ebe4;
            --primary-accent: #9b8c7c;
            --secondary-accent: #b9a89b;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --info: #3B82F6;
            --success-light: #e8f3ed;
            --warning-light: #fff1e0;
            --danger-light: #ffe9e9;
            --info-light: #e6f0ff;
        }
        
        [data-theme="dark"] {
            --bg-primary: #1a1e24;
            --bg-gradient: radial-gradient(circle at top right, #232931, #1a1f26);
            --text-primary: #e6e9f0;
            --text-secondary: #b4bcca;
            --text-muted: #8e99aa;
            --card-bg: rgba(35, 41, 49, 0.85);
            --card-border: rgba(75, 85, 99, 0.2);
            --shadow-color: rgba(0, 0, 0, 0.3);
            --hover-bg: #2d353f;
            --input-bg: #2d353f;
            --input-border: #404b5a;
            --badge-bg: #2d353f;
            --primary-accent: #b9a89b;
            --secondary-accent: #9b8c7b;
            --success-light: #1e2b24;
            --warning-light: #2e281c;
            --danger-light: #2e1e1e;
            --info-light: #1e2a3a;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
            line-height: 1.5;
            -webkit-tap-highlight-color: transparent;
        }
        
        .gradient-bg {
            background: var(--bg-gradient);
            min-height: 100vh;
            padding: 12px;
            position: relative;
            overflow: hidden;
            transition: background 0.3s ease;
        }
        
        .container { max-width: 1400px; margin: 0 auto; position: relative; width: 100%; }
        
        .main-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 10px 15px;
            margin-bottom: 15px;
            border: 1px solid var(--card-border);
            position: relative;
            z-index: 100;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .header-left, .header-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        
        .burger-menu-container { position: relative; }
        
        .burger-btn {
            background: var(--primary-accent);
            border: none;
            border-radius: 40px;
            padding: 10px 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
            color: white;
        }
        
        .burger-btn:hover { background: var(--secondary-accent); transform: scale(1.02); }
        .burger-icon { font-size: 1.2rem; }
        .burger-text { font-size: 0.85rem; font-weight: 500; }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 10px;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 10px;
            min-width: 280px;
            max-height: 70vh;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            flex-direction: column;
            gap: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            animation: fadeInDown 0.2s ease;
        }
        
        .dropdown-menu::-webkit-scrollbar { width: 6px; }
        .dropdown-menu::-webkit-scrollbar-track { background: var(--card-border); border-radius: 3px; }
        .dropdown-menu::-webkit-scrollbar-thumb { background: var(--primary-accent); border-radius: 3px; }
        .dropdown-menu.show { display: flex; }
        
        .dropdown-item {
            background: var(--badge-bg);
            border-radius: 14px;
            padding: 12px 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover { background: var(--hover-bg); transform: translateX(5px); }
        .dropdown-item.active { background: var(--primary-accent); color: white; }
        .dropdown-item.active .dropdown-icon,
        .dropdown-item.active .dropdown-title,
        .dropdown-item.active .dropdown-desc { color: white; }
        
        .dropdown-icon { font-size: 1.2rem; min-width: 32px; text-align: center; }
        .dropdown-info { flex: 1; }
        .dropdown-title { font-size: 0.85rem; font-weight: 600; color: var(--text-primary); }
        .dropdown-desc { font-size: 0.6rem; color: var(--text-muted); }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .dropdown-menu {
                position: fixed;
                top: 70px;
                left: 12px;
                right: 12px;
                width: auto;
                max-height: calc(100vh - 100px);
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.2);
                animation: fadeInDown 0.2s ease;
                z-index: 999;
            }
            
            .burger-btn {
                padding: 8px 14px;
            }
            
            .burger-icon {
                font-size: 1rem;
            }
            
            .burger-text {
                font-size: 0.75rem;
            }
            
            .header-left {
                flex: 1;
            }
            
            .header-right {
                width: 100%;
                justify-content: space-between;
                order: 2;
            }
            
            .logo-area {
                order: 1;
            }
            
            .user-badge {
                font-size: 0.7rem;
                padding: 4px 10px;
            }
            
            .theme-toggle {
                padding: 4px 8px;
                font-size: 0.7rem;
            }
        }
        
        /* Test Mode Button Styles */
        #testModeBtn {
            transition: all 0.3s ease;
            font-weight: 600;
            cursor: pointer;
        }
        
        #testModeBtn:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        /* Test mode active indicator */
        .test-mode-active {
            border: 2px solid #F59E0B !important;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.3);
        }
        
        .user-badge {
            background: var(--badge-bg);
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 500;
            border: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .theme-toggle {
            background: var(--badge-bg);
            border: 1px solid var(--card-border);
            border-radius: 30px;
            padding: 6px 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.75rem;
        }
        
        .notification-bell-container {
            position: relative;
            cursor: pointer;
            z-index: 100;
        }
        
        .notification-bell {
            background: var(--badge-bg);
            border: 1px solid var(--card-border);
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
            z-index: 101;
        }
        
        .notification-bell:hover { background: var(--hover-bg); transform: scale(1.05); }
        
        .notification-bell .bell-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            z-index: 102;
        }
        
        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 10px;
            width: 320px;
            max-width: 90vw;
            max-height: 70vh;
            overflow-y: auto;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            z-index: 1000;
            display: none;
            flex-direction: column;
            animation: fadeInDown 0.2s ease;
        }
        
        .notification-dropdown.show { display: flex; }
        
        .notification-dropdown-header {
            padding: 12px 15px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            z-index: 1;
        }
        
        .notification-dropdown-header h4 { font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 5px; }
        .notification-dropdown-header button { background: none; border: none; cursor: pointer; font-size: 0.7rem; color: var(--text-muted); padding: 4px 8px; border-radius: 20px; transition: all 0.2s; }
        .notification-dropdown-header button:hover { background: var(--hover-bg); color: var(--text-primary); }
        
        .notification-dropdown-list { max-height: calc(70vh - 60px); overflow-y: auto; padding: 8px; }
        
        .notification-dropdown-item {
            padding: 10px;
            margin-bottom: 6px;
            border-radius: 12px;
            background: var(--badge-bg);
            border-left: 3px solid;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .notification-dropdown-item:hover { background: var(--hover-bg); transform: translateX(-3px); }
        .notification-dropdown-item.info { border-left-color: var(--info); }
        .notification-dropdown-item.success { border-left-color: var(--success); }
        .notification-dropdown-item.warning { border-left-color: var(--warning); }
        .notification-dropdown-item.error { border-left-color: var(--danger); }
        
        .notification-dropdown-time { font-size: 0.55rem; color: var(--text-muted); margin-bottom: 4px; }
        .notification-dropdown-message { font-size: 0.7rem; color: var(--text-primary); line-height: 1.4; }
        .empty-notifications-dropdown { text-align: center; padding: 30px 15px; color: var(--text-muted); font-size: 0.75rem; }
        
        .logo-area { display: flex; align-items: center; gap: 8px; }
        .logo-small { height: 40px; width: auto; border-radius: 10px; }
        .logo-area h1 { font-size: 1.1rem; font-weight: 600; color: var(--text-primary); }
        .logo-area .subtitle { font-size: 0.55rem; color: var(--text-muted); }
        
        .arduino-status-bar {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 40px;
            padding: 8px 12px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            border: 1px solid var(--card-border);
        }
        
        .live-dot { 
            width: 8px; 
            height: 8px; 
            border-radius: 50%; 
            background: #9ca3af;
            transition: all 0.3s ease;
        }
        .live-dot.connected { 
            background: var(--success); 
            animation: pulse 2s infinite; 
        }
        .live-dot.connecting {
            background: #f59e0b;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }
        
        .main-content {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 25px;
            border: 1px solid var(--card-border);
            min-height: 500px;
            transition: all 0.3s ease;
            overflow-y: auto;
            max-height: calc(100vh - 180px);
        }
        
        .panel-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--card-border);
        }
        
        .panel-header .panel-icon { font-size: 1.5rem; }
        .panel-header h2 { font-size: 1.2rem; font-weight: 600; color: var(--text-primary); }
        
        .live-status-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        
        .live-status-item {
            background: var(--badge-bg);
            border-radius: 16px;
            padding: 15px;
            text-align: center;
        }
        
        .live-status-label { font-size: 0.7rem; color: var(--text-muted); margin-bottom: 6px; }
        .live-status-value { font-size: 1rem; font-weight: 600; color: var(--text-primary); }
        
        .recent-feedings-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
        .recent-feedings-table th, .recent-feedings-table td { padding: 12px 10px; text-align: left; border-bottom: 1px solid var(--card-border); }
        .recent-feedings-table th { color: var(--text-muted); font-weight: 500; }
        .recent-feedings-table tr:hover { background: var(--hover-bg); }
        
        .empty-feedings { text-align: center; padding: 40px; color: var(--text-muted); font-size: 0.85rem; }
        
        .view-all-link { text-align: center; margin-top: 20px; }
        .view-all-link button { background: var(--badge-bg); border: 1px solid var(--card-border); padding: 8px 16px; border-radius: 25px; cursor: pointer; font-size: 0.75rem; transition: all 0.2s; }
        .view-all-link button:hover { background: var(--hover-bg); color: var(--primary-accent); }
        
        .log-entries { max-height: 500px; overflow-y: auto; }
        
        .log-entry {
            display: flex;
            padding: 12px;
            background: var(--badge-bg);
            border-radius: 12px;
            margin-bottom: 8px;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }
        
        .log-entry:hover { transform: translateX(5px); background: var(--hover-bg); }
        .log-time { width: 80px; color: var(--text-muted); font-family: monospace; }
        .log-message { flex: 1; color: var(--text-secondary); }
        
        .log-filter-btn {
            padding: 6px 14px;
            border-radius: 20px;
            background: var(--badge-bg);
            border: 1px solid var(--card-border);
            cursor: pointer;
            font-size: 0.7rem;
            transition: all 0.2s;
        }
        
        .log-filter-btn:hover { transform: translateY(-2px); box-shadow: 0 2px 8px var(--shadow-color); }
        .log-filter-btn.active { background: var(--primary-accent) !important; color: white !important; border-color: var(--primary-accent) !important; }
        
        .portion-badge { background: var(--success); color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; }
        
        .history-filter-btn { padding: 4px 10px; border-radius: 18px; background: var(--badge-bg); border: 1px solid var(--card-border); cursor: pointer; font-size: 0.7rem; margin-left: 8px; }
        .history-filter-btn.active { background: var(--primary-accent); color: white; }
        
        .toast-notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 24px;
            border-radius: 40px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 2000;
            font-size: 0.85rem;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            animation: slideDownCenter 0.3s ease forwards;
            pointer-events: none;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            text-align: center;
            max-width: 90vw;
            width: auto;
            white-space: normal;
            word-break: break-word;
        }
        
        @media (max-width: 768px) {
            .toast-notification { top: 10px; font-size: 0.75rem; padding: 10px 16px; max-width: 90vw; }
        }
        
        .toast-notification.success { background: linear-gradient(135deg, #10B981, #059669); }
        .toast-notification.error { background: linear-gradient(135deg, #EF4444, #DC2626); }
        .toast-notification.warning { background: linear-gradient(135deg, #F59E0B, #D97706); }
        .toast-notification.info { background: linear-gradient(135deg, #3B82F6, #2563EB); }
        
        .journal-stats { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .journal-entries { max-height: 500px; overflow-y: auto; }
        
        .journal-note {
            background: var(--badge-bg);
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 12px;
            border-left: 4px solid var(--primary-accent);
            transition: all 0.2s ease;
            position: relative;
            animation: slideIn 0.3s ease;
        }
        
        .journal-note:hover { transform: translateX(5px); background: var(--hover-bg); }
        .journal-note.deleted { opacity: 0.7; border-left-color: var(--danger); background: var(--danger-light); }
        
        .note-header { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; flex-wrap: wrap; }
        .note-pet-icon { font-size: 1.2rem; background: var(--card-bg); width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid var(--card-border); }
        .note-pet-name { font-weight: 600; color: var(--text-primary); font-size: 1rem; }
        .note-title { font-weight: 500; color: var(--text-secondary); font-size: 0.85rem; background: var(--card-bg); padding: 4px 12px; border-radius: 30px; border: 1px solid var(--card-border); }
        .note-time { font-size: 0.7rem; color: var(--text-muted); margin-left: auto; display: flex; align-items: center; gap: 4px; }
        .note-content { color: var(--text-secondary); line-height: 1.6; font-size: 0.9rem; padding: 8px 0; white-space: pre-wrap; word-break: break-word; }
        
        .note-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 8px; border-top: 1px dashed var(--card-border); }
        .note-type { font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px; }
        .note-actions { display: flex; gap: 8px; }
        
        .note-action-btn {
            background: none;
            border: 1px solid transparent;
            font-size: 1rem;
            cursor: pointer;
            padding: 6px;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            color: var(--text-muted);
        }
        
        .note-action-btn:hover { background: var(--card-bg); border-color: var(--card-border); color: var(--text-primary); transform: scale(1.1); }
        .note-action-btn.delete:hover { color: var(--danger); border-color: var(--danger); }
        .note-action-btn.restore:hover { color: var(--success); border-color: var(--success); }
        .note-action-btn.permanent-delete:hover { color: var(--danger); border-color: var(--danger); background: var(--danger-light); }
        
        .empty-state { text-align: center; padding: 40px 20px; color: var(--text-muted); font-style: italic; background: var(--badge-bg); border-radius: 20px; border: 1px solid var(--card-border); }
        
        .card { background: var(--badge-bg); border-radius: 16px; padding: 15px; margin-bottom: 15px; border: 1px solid var(--card-border); }
        .card-header { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
        
        .btn-feed {
            width: 100%; background: var(--badge-bg); border: 1px solid var(--card-border);
            padding: 12px; border-radius: 40px; display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 15px; cursor: pointer;
            font-size: 0.85rem;
        }
        
        .portion-selector { background: var(--badge-bg); padding: 15px; border-radius: 16px; margin-bottom: 15px; }
        .selector-label { font-size: 0.75rem; display: block; margin-bottom: 8px; }
        #portionSlider { width: 100%; height: 4px; }
        
        .btn-reset { width: 100%; background: var(--badge-bg); border: 1px solid var(--card-border); padding: 10px; border-radius: 40px; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; font-size: 0.75rem; }
        
        .water-status-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px; }
        
        .water-widget { background: var(--badge-bg); padding: 12px; border-radius: 14px; }
        .water-widget-row { display: flex; align-items: center; gap: 10px; justify-content: space-between; }
        .water-bar-track { width: 5px; height: 60px; background: var(--card-border); border-radius: 3px; position: relative; overflow: hidden; }
        .water-bar-fill { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(180deg, #60a5fa, #1d4ed8); transition: height 0.5s ease; }
        
        .water-notifications-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .water-notification-item {
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 12px;
            background: var(--badge-bg);
            border-left: 3px solid;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .water-notification-item:hover { background: var(--hover-bg); transform: translateX(-3px); }
        .water-notification-item.info { border-left-color: var(--info); }
        .water-notification-item.success { border-left-color: var(--success); }
        .water-notification-item.warning { border-left-color: var(--warning); }
        .water-notification-item.error { border-left-color: var(--danger); }
        
        .water-notification-item .notification-time { font-size: 0.55rem; color: var(--text-muted); margin-bottom: 4px; }
        .water-notification-item .notification-message { font-size: 0.7rem; color: var(--text-primary); line-height: 1.4; }
        
        .weight-visual { display: flex; gap: 12px; align-items: center; }
        .weight-tank { width: 45px; height: 100px; background: var(--badge-bg); border-radius: 22px; position: relative; overflow: hidden; }
        .weight-fill { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(180deg, var(--primary-accent), var(--secondary-accent)); transition: height 0.3s ease; }
        
        .schedule-header {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        
        .add-schedule-btn {
            background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent));
            border: none;
            border-radius: 40px;
            padding: 10px 22px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        
        .add-schedule-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .schedule-item {
            background: var(--badge-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            transition: all 0.2s;
        }
        
        .schedule-item:hover {
            transform: translateX(5px);
            border-color: var(--primary-accent);
        }
        
        .schedule-item.processed {
            background: var(--success-light);
            opacity: 0.85;
        }
        
        .schedule-info {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .meal-info {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 110px;
        }
        
        .meal-icon { font-size: 1.8rem; }
        .meal-name { font-weight: 700; font-size: 1rem; }
        .meal-label { font-size: 0.65rem; color: var(--text-muted); margin-top: 2px; }
        
        .time-display {
            font-size: 1.1rem;
            font-weight: 600;
            font-family: monospace;
            color: var(--primary-accent);
            min-width: 100px;
        }
        
        .portion-display {
            background: var(--card-bg);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .status-badge.active {
            background: var(--success-light);
            color: var(--success);
        }
        
        .status-badge.inactive {
            background: var(--danger-light);
            color: var(--danger);
        }
        
        .status-badge.processed {
            background: var(--info-light);
            color: var(--info);
        }
        
        .last-trigger {
            font-size: 0.65rem;
            color: var(--text-muted);
            min-width: 140px;
        }
        
        .schedule-actions {
            display: flex;
            gap: 10px;
        }
        
        .edit-schedule-btn, .delete-schedule-btn {
            padding: 6px 16px;
            border-radius: 25px;
            font-size: 0.7rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        
        .edit-schedule-btn {
            background: var(--info-light);
            color: var(--info);
            border: 1px solid var(--info);
        }
        
        .edit-schedule-btn:hover {
            background: var(--info);
            color: white;
        }
        
        .delete-schedule-btn {
            background: var(--danger-light);
            color: var(--danger);
            border: 1px solid var(--danger);
        }
        
        .delete-schedule-btn:hover {
            background: var(--danger);
            color: white;
        }
        
        .empty-schedule {
            text-align: center;
            padding: 60px 20px;
            background: var(--badge-bg);
            border-radius: 24px;
            border: 1px solid var(--card-border);
        }
        
        .empty-schedule-icon { font-size: 4rem; margin-bottom: 15px; }
        .empty-schedule h3 { margin-bottom: 10px; font-size: 1.1rem; }
        .empty-schedule p { color: var(--text-muted); font-size: 0.85rem; }
        
        @media (max-width: 768px) {
            .schedule-item { flex-direction: column; align-items: stretch; }
            .schedule-info { flex-direction: column; align-items: flex-start; gap: 10px; }
            .schedule-actions { justify-content: flex-end; }
            .main-content { padding: 15px; }
            .live-status-grid { grid-template-columns: 1fr; gap: 10px; }
            .water-status-grid { grid-template-columns: 1fr; gap: 10px; }
            .recent-feedings-table { font-size: 0.7rem; }
            .recent-feedings-table th, .recent-feedings-table td { padding: 8px 6px; }
            .journal-stats { flex-direction: column; gap: 8px; }
        }
        
        .stat-pill { background: var(--badge-bg); padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 5px; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; background: var(--badge-bg); cursor: pointer; }
        .btn-tare { background: var(--badge-bg); border: 1px solid var(--card-border); padding: 6px 12px; border-radius: 30px; cursor: pointer; margin-top: 8px; font-size: 0.7rem; }
        
        .feed-again-option { transition: all 0.2s ease; }
        .feed-again-option:hover { transform: translateY(-3px); box-shadow: 0 8px 20px var(--shadow-color); }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1001;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        
        .modal-content {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 20px;
            max-width: 450px;
            width: 100%;
            max-height: 85vh;
            overflow-y: auto;
        }
        
        .close-modal { float: right; cursor: pointer; font-size: 20px; padding: 5px; }
        
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 4px; font-weight: 500; font-size: 0.75rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 10px; border-radius: 10px; border: 1px solid var(--card-border); background: var(--input-bg); font-size: 0.75rem; }
        
        .form-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 12px; }
        .btn-save { background: var(--primary-accent); color: white; border: none; padding: 8px 16px; border-radius: 25px; cursor: pointer; font-size: 0.7rem; }
        .btn-cancel { background: var(--badge-bg); border: 1px solid var(--card-border); padding: 8px 16px; border-radius: 25px; cursor: pointer; font-size: 0.7rem; }
        .btn-danger { background: var(--danger); color: white; border: none; padding: 8px 16px; border-radius: 25px; cursor: pointer; font-size: 0.7rem; transition: all 0.2s; }
        .btn-danger:hover { opacity: 0.9; transform: scale(1.02); }
        
        .time-select-group { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .time-select-group select { flex: 1; min-width: 60px; }
        
        .plate-option { padding: 12px; text-align: center; background: var(--badge-bg); border-radius: 12px; cursor: pointer; border: 2px solid transparent; }
        .plate-option.selected { border-color: var(--primary-accent); background: var(--hover-bg); }
        
        .schedule-status-info { margin-top: 10px; padding: 8px; background: var(--info-light); border-radius: 8px; font-size: 0.7rem; border-left: 3px solid var(--info); }
        
        .water-badge {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        #foodEmptyBanner, #waterContainerMissingBanner, #plateTimeoutBanner { animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        
        .meal-dropdown-group {
            margin-bottom: 12px;
        }
        .meal-dropdown-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        #mealSelect {
            width: 100%;
            padding: 8px 10px;
            border-radius: 10px;
            border: 1px solid var(--card-border);
            background: var(--input-bg);
            font-size: 0.75rem;
        }

        /* Schedule Modal Text Colors - White Text Fix */
#scheduleModal .modal-content,
#scheduleModal .modal-content * {
    color: var(--text-primary);
}

#scheduleModal .modal-content h3,
#scheduleModal .modal-content label,
#scheduleModal .modal-content .form-group label,
#scheduleModal .modal-content .schedule-status-info,
#scheduleModal .modal-content .schedule-status-info strong,
#scheduleModal .modal-content .schedule-status-info br {
    color: var(--text-primary);
}

#scheduleModal .modal-content input,
#scheduleModal .modal-content select,
#scheduleModal .modal-content textarea {
    color: var(--text-primary);
    background: var(--input-bg);
}

#scheduleModal .modal-content input::placeholder,
#scheduleModal .modal-content select::placeholder,
#scheduleModal .modal-content textarea::placeholder {
    color: var(--text-muted);
}

#scheduleModal .modal-content .btn-cancel,
#scheduleModal .modal-content .btn-save,
#scheduleModal .modal-content .btn-danger {
    color: white;
}

#scheduleModal .modal-content .btn-cancel {
    background: var(--badge-bg);
    color: var(--text-primary);
    border: 1px solid var(--card-border);
}

#scheduleModal .modal-content .btn-save {
    background: var(--primary-accent);
    color: white;
}

#scheduleModal .modal-content .btn-danger {
    background: var(--danger);
    color: white;
}

#scheduleModal .modal-content .schedule-status-info {
    background: var(--info-light);
    color: var(--text-primary);
    border-left-color: var(--info);
}

#scheduleModal .modal-content .close-modal {
    color: var(--text-primary);
}

#scheduleModal .modal-content .close-modal:hover {
    color: var(--danger);
}

/* For dark mode compatibility */
[data-theme="dark"] #scheduleModal .modal-content,
[data-theme="dark"] #scheduleModal .modal-content * {
    color: #e6e9f0;
}

[data-theme="dark"] #scheduleModal .modal-content input,
[data-theme="dark"] #scheduleModal .modal-content select,
[data-theme="dark"] #scheduleModal .modal-content textarea {
    background: #2d353f;
    color: #e6e9f0;
    border-color: #404b5a;
}

[data-theme="dark"] #scheduleModal .modal-content .btn-cancel {
    background: #2d353f;
    color: #e6e9f0;
}

[data-theme="dark"] #scheduleModal .modal-content .schedule-status-info {
    background: #1e2a3a;
    color: #e6e9f0;
}
    </style>
</head>
<body data-theme="light">
<div class="gradient-bg">
<div class="container">

    <!-- Food Empty Banner -->
    <div id="foodEmptyBanner" style="display:none;
        background:linear-gradient(135deg,#EF4444,#dc2626);color:white;
        border-radius:16px;padding:16px 24px;margin-bottom:20px;
        align-items:center;gap:16px;box-shadow:0 4px 12px rgba(239,68,68,0.3);
        animation:slideIn 0.3s ease;">
        <span style="font-size:2rem;">⚠️</span>
        <div style="flex:1;">
            <div style="font-weight:600;font-size:1.1rem;">Food Storage Empty!</div>
            <div style="font-size:0.9rem;opacity:0.9;">Ang food dispenser ay maaaring walang laman na. Mag-refill agad.</div>
        </div>
        <button onclick="dismissFoodEmptyWarning()"
            style="background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.4);
            color:white;padding:8px 16px;border-radius:30px;cursor:pointer;font-size:0.9rem;">
            Dismiss
        </button>
    </div>

    <!-- HEADER -->
    <div class="main-header">
        <div class="header-left">
            <div class="burger-menu-container">
                <button class="burger-btn" id="burgerBtn" onclick="toggleDropdownMenu(event)">
                    <span class="burger-icon">☰</span>
                    <span class="burger-text">Menu</span>
                </button>
                <div class="dropdown-menu" id="dropdownMenu">
                    <div class="dropdown-item" data-panel="liveStatus" onclick="selectMenuItem('liveStatus')"><div class="dropdown-icon">📊</div><div class="dropdown-info"><div class="dropdown-title">Live Status</div><div class="dropdown-desc">Real-time system status</div></div></div>
                    <div class="dropdown-item" data-panel="activityLog" onclick="selectMenuItem('activityLog')"><div class="dropdown-icon">📝</div><div class="dropdown-info"><div class="dropdown-title">Activity Log</div><div class="dropdown-desc">System activity history</div></div></div>
                    <div style="height: 1px; background: var(--card-border); margin: 5px 0;"></div>
                    <div class="dropdown-item" data-panel="feed" onclick="selectMenuItem('feed')"><div class="dropdown-icon">🍖</div><div class="dropdown-info"><div class="dropdown-title">Feed Now</div><div class="dropdown-desc">Manual feeding control</div></div></div>
                    <div class="dropdown-item" data-panel="water" onclick="selectMenuItem('water')"><div class="dropdown-icon">💧</div><div class="dropdown-info"><div class="dropdown-title">Water System</div><div class="dropdown-desc">Water level & notifications</div></div></div>
                    <div class="dropdown-item" data-panel="schedule" onclick="selectMenuItem('schedule')"><div class="dropdown-icon">⏰</div><div class="dropdown-info"><div class="dropdown-title">Scheduled Feeding</div><div class="dropdown-desc">Breakfast, Lunch, Dinner</div></div></div>
                    <div class="dropdown-item" data-panel="journal" onclick="selectMenuItem('journal')"><div class="dropdown-icon">📔</div><div class="dropdown-info"><div class="dropdown-title">Pet Journal</div><div class="dropdown-desc">Notes & memories</div></div></div>
                    <div class="dropdown-item" data-panel="watch" onclick="selectMenuItem('watch')"><div class="dropdown-icon">📹</div><div class="dropdown-info"><div class="dropdown-title">Watch My Pet</div><div class="dropdown-desc">AI camera pet detection</div></div></div>
                    <div class="dropdown-item" data-panel="manual" onclick="selectMenuItem('manual')"><div class="dropdown-icon">📖</div><div class="dropdown-info"><div class="dropdown-title">User Manual</div><div class="dropdown-desc">How to use IntelliFeed</div></div></div>
                    <div class="dropdown-item" data-panel="tips" onclick="selectMenuItem('tips')"><div class="dropdown-icon">💡</div><div class="dropdown-info"><div class="dropdown-title">Feeding Tips</div><div class="dropdown-desc">For dogs & cats</div></div></div>
                    <div style="height: 1px; background: var(--card-border); margin: 5px 0;"></div>
                    <div class="dropdown-item" onclick="window.location.href='logout.php'"><div class="dropdown-icon">🚪</div><div class="dropdown-info"><div class="dropdown-title">Logout</div><div class="dropdown-desc">Sign out of your account</div></div></div>
                </div>
            </div>
            <div class="user-badge"><span>👋</span> <?php echo htmlspecialchars($username); ?></div>
            <button id="themeToggle" class="theme-toggle" onclick="toggleTheme()"><span class="sun">☀️</span><span class="moon">🌙</span></button>
        </div>
        <div class="header-right">
            <div class="notification-bell-container">
                <div class="notification-bell" onclick="toggleNotificationDropdown(event)"><span>🔔</span><span class="bell-badge" id="bellBadge" style="display: none;">0</span></div>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-dropdown-header"><h4>🔔 Notifications</h4><div><button onclick="markAllNotificationsRead()">✓ Mark all read</button><button onclick="clearAllNotifications()" style="color: var(--danger);">🗑️ Clear</button></div></div>
                    <div class="notification-dropdown-list" id="notificationDropdownList"><div class="empty-notifications-dropdown">No notifications yet</div></div>
                </div>
            </div>
            <!-- TEST MODE BUTTON -->
            <button id="testModeBtn" class="theme-toggle" onclick="toggleTestMode()" style="background: var(--warning); color: white;">
                <span>🧪</span> Test Mode: OFF
            </button>
            <div class="logo-area"><img src="images/logo.jpg" alt="Logo" class="logo-small" onerror="this.style.display='none'"><div><h1>IntelliFeed+</h1><div class="subtitle">Smart Pet Care</div></div></div>
        </div>
    </div>

    <!-- Status Bar -->
    <div class="arduino-status-bar">
        <div class="status-indicator" style="display: flex; align-items: center; gap: 6px;">
            <div class="live-dot" id="connectionDot"></div>
            <span id="connectionText" style="font-size: 0.7rem; color: #EF4444;">Disconnected</span>
        </div>
        <div class="device-info" style="display: flex; gap: 10px; font-size: 0.65rem;">
            <span>📡 <span id="lastUpdate">--</span></span>
            <span>⚡ <span id="pendingCommands">0</span></span>
            <span>🍽️ <span id="plateStatusBar">--</span></span>
        </div>
    </div>

    <!-- MAIN CONTENT PANEL -->
    <div class="main-content" id="mainContent">
        <div id="panelContent">
            <div class="panel-header"><span class="panel-icon">📊</span><h2>Live Status</h2><button class="badge" onclick="refreshLiveStatus()" style="background: var(--info); color: white;">🔄 Refresh</button></div>
            <div class="live-status-grid">
                <div class="live-status-item"><div class="live-status-label">Last Feeding</div><div class="live-status-value" id="lastFeedingPanel">--</div></div>
                <div class="live-status-item"><div class="live-status-label">Weight on Plate</div><div class="live-status-value" id="weightLevelPanel">0.0 g</div></div>
                <div class="live-status-item"><div class="live-status-label">Feeding Status</div><div class="live-status-value" id="feedingStatusPanel">Idle</div></div>
                <div class="live-status-item"><div class="live-status-label">Plate Dispenser</div><div class="live-status-value" id="platePositionPanel">--</div></div>
                <div class="live-status-item"><div class="live-status-label">Food Storage</div><div class="live-status-value" id="foodLevelPanel">OK</div></div>
                <div class="live-status-item"><div class="live-status-label">Water Storage</div><div class="live-status-value" id="wStoragePanel">--%</div></div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Modals -->
<div id="plateModal" class="modal">
    <div class="modal-content"><span class="close-modal" onclick="closePlateModal()">&times;</span><h3 style="margin-bottom:10px;">🍖 Choose Plate Type</h3>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:10px;"><div class="plate-option" id="stockCard" onclick="selectPlate('stock')"><div style="font-size:1.5rem;">🫙</div><div style="font-size:0.8rem;">Stock Plate</div></div><div class="plate-option" id="newCard" onclick="selectPlate('new')"><div style="font-size:1.5rem;">🍽️</div><div style="font-size:0.8rem;">New Plate</div></div></div>
    <div id="plateConfirmRow" style="display:none;"><div id="selectedPlateLabel" style="font-size:0.8rem;"></div><div style="font-size:0.75rem;">Portion: <strong id="confirmPortion">30g</strong></div><div style="display:flex; gap:8px; margin-top:8px;"><button class="btn-cancel" onclick="closePlateModal()">Cancel</button><button class="btn-save" onclick="confirmFeed()">Confirm Feed</button></div></div></div>
</div>

<div id="feedAgainModal" class="modal">
    <div class="modal-content" style="max-width: 550px;"><span class="close-modal" onclick="closeFeedAgainModal()">&times;</span><h3 style="justify-content:center;">🍖 Feed Again</h3>
    <div id="feedAgainPlateInfo" style="background:var(--badge-bg);border-radius:12px;padding:12px;margin-bottom:15px;text-align:center;border:1px solid var(--card-border);"></div>
    <div style="margin-bottom:20px;text-align:center;color:var(--text-secondary);"><p>Ano ang gusto mong gawin sa kasalukuyang plate?</p></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px;">
        <button id="addMoreFoodBtn" class="btn-save feed-again-option" style="background:#3B82F6;display:flex;flex-direction:column;gap:5px;padding:15px;"><span style="font-size:1.8rem;">➕</span><span style="font-weight:600;">Add More Food</span><span style="font-size:0.7rem;">Magdagdag ng pagkain sa plate</span></button>
        <button id="disposeAndFeedBtn" class="btn-save feed-again-option" style="background:#F59E0B;display:flex;flex-direction:column;gap:5px;padding:15px;"><span style="font-size:1.8rem;">🗑️</span><span style="font-weight:600;">Dispose & Feed New</span><span style="font-size:0.7rem;">Itapon ang plate, maglagay ng bago</span></button>
    </div>
    <div style="margin-top:10px;padding:12px;background:var(--info-light);border-radius:12px;border:1px solid #3B82F6;"><div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;"><span style="font-size:1rem;">💡</span><span style="font-weight:500;">Paalala:</span></div><div style="font-size:0.8rem;color:var(--text-secondary);line-height:1.5;">• <strong>Add More Food</strong> - Magdadagdag lang ng pagkain sa kasalukuyang plate.<br>• <strong>Dispose & Feed New</strong> - Itatapon muna ang kasalukuyang plate, pagkatapos ay maglalagay ng bagong plate.</div></div>
    <button class="btn-cancel" onclick="closeFeedAgainModal()" style="width:100%;margin-top:15px;">Cancel</button></div>
</div>

<div id="journalModal" class="modal">
    <div class="modal-content"><span class="close-modal" onclick="closeJournalModal()">&times;</span><h3 style="font-size:1rem;">📝 New Journal Entry</h3>
    <div class="form-group"><label>Pet Name *</label><input type="text" id="journalPetName"></div>
    <div class="form-group"><label>Pet Type</label><select id="journalPetType"><option value="cat">🐱 Cat</option><option value="dog">🐶 Dog</option></select></div>
    <div class="form-group"><label>Title</label><input type="text" id="journalTitle"></div>
    <div class="form-group"><label>Note *</label><textarea id="journalNote" rows="3"></textarea></div>
    <div class="form-actions"><button class="btn-cancel" onclick="closeJournalModal()">Cancel</button><button class="btn-save" onclick="saveJournalEntry()">Save</button></div></div>
</div>

<!-- SCHEDULE MODAL -->
<div id="scheduleModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeScheduleModal()">&times;</span>
        <h3 id="slotModalTitle" style="font-size:1rem;">⏰ Set Schedule</h3>
        
        <div class="meal-dropdown-group">
            <label>🍽️ Select Meal *</label>
            <select id="mealSelect" onchange="onMealSelectChange()">
                <option value="breakfast">🌅 Breakfast</option>
                <option value="lunch">☀️ Lunch</option>
                <option value="dinner">🌙 Dinner</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Time (with seconds)</label>
            <div class="time-select-group">
                <select id="schedHour"><option value="12">12</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option></select>
                <span>:</span>
                <select id="schedMinute">
                    <option value="00">00</option><option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option>
                    <option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option>
                    <option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option>
                    <option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option>
                    <option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option>
                    <option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option>
                    <option value="30">30</option><option value="31">31</option><option value="32">32</option><option value="33">33</option><option value="34">34</option>
                    <option value="35">35</option><option value="36">36</option><option value="37">37</option><option value="38">38</option><option value="39">39</option>
                    <option value="40">40</option><option value="41">41</option><option value="42">42</option><option value="43">43</option><option value="44">44</option>
                    <option value="45">45</option><option value="46">46</option><option value="47">47</option><option value="48">48</option><option value="49">49</option>
                    <option value="50">50</option><option value="51">51</option><option value="52">52</option><option value="53">53</option><option value="54">54</option>
                    <option value="55">55</option><option value="56">56</option><option value="57">57</option><option value="58">58</option><option value="59">59</option>
                </select>
                <span>:</span>
                <select id="schedSecond">
                    <option value="00">00</option><option value="05">05</option><option value="10">10</option><option value="15">15</option>
                    <option value="20">20</option><option value="25">25</option><option value="30">30</option><option value="35">35</option>
                    <option value="40">40</option><option value="45">45</option><option value="50">50</option><option value="55">55</option>
                </select>
                <select id="schedAmPm"><option value="AM">AM</option><option value="PM">PM</option></select>
            </div>
        </div>
        <div class="form-group"><label>Portion (g)</label><input type="number" id="schedPortion" value="30" min="10" max="500" step="5"></div>
        <div class="form-group"><label>Label (optional)</label><input type="text" id="schedCustomLabel" placeholder="e.g., Morning Meal"></div>
        <div class="form-group"><label>Status</label><select id="schedStatus"><option value="1">✅ Active - Will trigger automatically</option><option value="0">⏸️ Inactive - Won't trigger</option></select></div>
        <div class="form-actions" style="display: flex; gap: 8px; justify-content: space-between; margin-top: 12px;">
            <div><button id="deleteScheduleBtn" class="btn-danger" onclick="deleteSchedule()" style="display: none;">🗑️ Delete Schedule</button></div>
            <div style="display: flex; gap: 8px;"><button class="btn-cancel" onclick="closeScheduleModal()">Cancel</button><button class="btn-save" onclick="saveSchedule()">💾 Save Schedule</button></div>
        </div>
    </div>
</div>

<div id="trashModal" class="modal">
    <div class="modal-content" style="max-width: 600px;"><span class="close-modal" onclick="closeTrashModal()">&times;</span><h3><span class="card-icon">🗑️</span> Trash</h3>
    <div class="trash-header" style="display: flex; gap: 10px; margin-bottom: 20px;"><button class="btn-danger" onclick="emptyTrash()" style="background: var(--danger); color: white; border: none; padding: 8px 16px; border-radius: 25px; cursor: pointer;">🗑️ Empty Trash</button><button class="btn-secondary" onclick="loadTrash()" style="background: var(--badge-bg); border: 1px solid var(--card-border); padding: 8px 16px; border-radius: 25px; cursor: pointer;">🔄 Refresh</button></div>
    <div class="journal-entries" id="trashEntries" style="max-height: 400px;"><div class="loading-message">Loading trash...</div></div></div>
</div>

<script>
const ESP32_BASE = 'http://10.213.15.60';
const API_URL = 'api/';
const SCHED_API = API_URL + 'save_schedule.php';
const JOURNAL_API_URL = API_URL + 'pet_journal.php';
const HISTORY_API_URL = API_URL + 'get_feeding_history.php';
const MAX_NOTE_LENGTH = 500;
const FEED_COOLDOWN_MS = 3000;
const WATER_PLATE_MIN_GRAMS = 3.5;

let currentPlateInside = false;
let currentNoPlate = false;
let selectedPlateType = null;
let slotData = [null, null, null];
let activeSlotIndex = 0;
let currentFilter = 'today';
let recentFilter = 'today';
let dropdownOpen = false;
let notificationDropdownOpen = false;
let currentPanel = 'liveStatus';
let lastFeedingTime = 0;
let lastFeedingAmount = 0;
let currentFeedPortion = 30;
let foodEmptyDismissed = false;
let waterContainerMissing = false;
let waterContainerMissingDismissed = false;
let _lastKnownWeight = 0;

let activityLogEntries = [];
let currentLogFilter = 'all';
let scheduleLastTrigger = [null, null, null];
let scheduleCheckInterval = null;
let isEditingSchedule = false;
let currentScheduleId = null;
let editingSlotIndex = null;

let _connState = null;
let _foodEmptyState = false;
let _waterLowState = false;
let _waterContainerOK = true;
let _noPlateState = false;

let notifications = [];
let notificationId = 0;
let waterNotifications = [];
let waterNotificationId = 0;
let testMode = false;  // TEST MODE VARIABLE

// ==================== TEST MODE FUNCTIONS ====================
function toggleTestMode() {
    testMode = !testMode;
    const btn = document.getElementById('testModeBtn');
    const mainContent = document.getElementById('mainContent');
    
    if (testMode) {
        btn.style.background = '#F59E0B';
        btn.style.color = 'white';
        btn.innerHTML = '<span>🧪</span> Test Mode: ON';
        if (mainContent) mainContent.classList.add('test-mode-active');
        addSystemEvent('🧪 TEST MODE ENABLED - All feeding actions will be simulated only', 'warning');
        showToastNotification('🧪 Test Mode: ON - Simulated feeding only', 'warning');
    } else {
        btn.style.background = 'var(--badge-bg)';
        btn.style.color = 'var(--text-primary)';
        btn.innerHTML = '<span>🧪</span> Test Mode: OFF';
        if (mainContent) mainContent.classList.remove('test-mode-active');
        addSystemEvent('✅ Test mode disabled - Returning to normal operation', 'success');
        showToastNotification('✅ Test Mode: OFF - Normal operation', 'success');
    }
    
    localStorage.setItem('testMode', testMode);
}

function loadTestModePreference() {
    const savedTestMode = localStorage.getItem('testMode');
    if (savedTestMode === 'true') {
        testMode = false;
        toggleTestMode();
    }
}

// ==================== HELPER FUNCTIONS ====================
function addSystemEvent(message, type = 'info', logOnly = false) {
    let category = 'system';
    const lowerMsg = message.toLowerCase();
    if (lowerMsg.includes('food') || lowerMsg.includes('feed') || lowerMsg.includes('plate') || lowerMsg.includes('portion') || lowerMsg.includes('dispose') || lowerMsg.includes('storage empty') || lowerMsg.includes('refill') || lowerMsg.includes('stock')) category = 'food';
    if (lowerMsg.includes('water') || lowerMsg.includes('pump') || lowerMsg.includes('container') || lowerMsg.includes('water storage') || lowerMsg.includes('water level')) category = 'water';
    if (lowerMsg.includes('schedule') || lowerMsg.includes('breakfast') || lowerMsg.includes('lunch') || lowerMsg.includes('dinner') || lowerMsg.includes('auto feed') || lowerMsg.includes('saved schedule') || lowerMsg.includes('triggered') || lowerMsg.includes('deleted') || lowerMsg.includes('updated')) category = 'schedule';
    addToArduinoLog(message, category);
    if (!logOnly && category !== 'water') {
        addNotification(message, type);
    }
}

function addNotification(message, type = 'info') {
    const id = ++notificationId;
    const timestamp = new Date();
    notifications.unshift({ id, message, type, timestamp, timeStr: timestamp.toLocaleTimeString(), dateStr: timestamp.toLocaleDateString(), read: false });
    if (notifications.length > 50) notifications.pop();
    renderNotificationDropdown();
    updateBellBadge();
    showToastNotification(message, type);
    saveNotificationsToStorage();
}

function addWaterNotification(message, type = 'info') {
    const id = ++waterNotificationId;
    const timestamp = new Date();
    waterNotifications.unshift({ id, message, type, timestamp, timeStr: timestamp.toLocaleTimeString(), dateStr: timestamp.toLocaleDateString(), read: false });
    if (waterNotifications.length > 50) waterNotifications.pop();
    if (currentPanel === 'water') {
        renderWaterNotifications();
    }
    if (type === 'error' || type === 'warning') {
        showToastNotification(message, type);
    }
    saveWaterNotificationsToStorage();
    addToArduinoLog(message, 'water');
}

function renderWaterNotifications() {
    const container = document.getElementById('waterNotificationsList');
    if (!container) return;
    if (waterNotifications.length === 0) {
        container.innerHTML = '<div class="empty-notifications-dropdown">💧 No water notifications yet</div>';
        return;
    }
    container.innerHTML = waterNotifications.map(notif => `
        <div class="water-notification-item ${notif.type}" onclick="markWaterNotificationRead(${notif.id})">
            <div class="notification-time">${notif.timeStr} · ${notif.dateStr}</div>
            <div class="notification-message">
                <span>${getWaterNotificationIcon(notif.type)}</span> ${escapeHtml(notif.message)}
            </div>
        </div>
    `).join('');
    updateWaterBadge();
}

function getWaterNotificationIcon(type) {
    return { error: '❌', warning: '⚠️', success: '✅', info: '💧' }[type] || '💧';
}

function updateWaterBadge() {
    const unreadCount = waterNotifications.filter(n => !n.read).length;
    const badge = document.getElementById('waterBadge');
    if (!badge) return;
    if (unreadCount > 0) {
        badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        badge.style.display = 'inline-flex';
    } else {
        badge.style.display = 'none';
    }
}

function markWaterNotificationRead(id) {
    const notif = waterNotifications.find(n => n.id === id);
    if (notif) { 
        notif.read = true; 
        renderWaterNotifications(); 
        updateWaterBadge(); 
        saveWaterNotificationsToStorage();
    }
}

function clearAllWaterNotifications() {
    if (confirm('Clear all water notifications?')) {
        waterNotifications = [];
        renderWaterNotifications();
        updateWaterBadge();
        saveWaterNotificationsToStorage();
        addSystemEvent('All water notifications cleared', 'info', true);
    }
}

function saveWaterNotificationsToStorage() {
    try { localStorage.setItem('intellifeed_water_notifications', JSON.stringify(waterNotifications)); } catch(e) {}
}

function loadWaterNotificationsFromStorage() {
    try {
        const saved = localStorage.getItem('intellifeed_water_notifications');
        if (saved) {
            waterNotifications = JSON.parse(saved).map(n => ({
                ...n,
                timestamp: new Date(n.timestamp),
                timeStr: new Date(n.timestamp).toLocaleTimeString(),
                dateStr: new Date(n.timestamp).toLocaleDateString()
            }));
            waterNotificationId = waterNotifications.length > 0 ? Math.max(...waterNotifications.map(n => n.id)) : 0;
            if (currentPanel === 'water') {
                renderWaterNotifications();
                updateWaterBadge();
            }
        }
    } catch(e) {}
}

function renderNotificationDropdown() {
    const container = document.getElementById('notificationDropdownList');
    if (!container) return;
    if (notifications.length === 0) {
        container.innerHTML = '<div class="empty-notifications-dropdown">📭 No notifications yet</div>';
        return;
    }
    container.innerHTML = notifications.map(notif => `<div class="notification-dropdown-item ${notif.type} ${notif.read ? 'read' : ''}" onclick="markNotificationRead(${notif.id})"><div class="notification-dropdown-time">${notif.timeStr} · ${notif.dateStr}</div><div class="notification-dropdown-message"><span>${getNotificationIcon(notif.type)}</span> ${escapeHtml(notif.message)}</div></div>`).join('');
}

function getNotificationIcon(type) { return { error: '❌', warning: '⚠️', success: '✅', info: 'ℹ️' }[type] || 'ℹ️'; }

function updateBellBadge() {
    const unreadCount = notifications.filter(n => !n.read).length;
    const badge = document.getElementById('bellBadge');
    if (!badge) return;
    if (unreadCount > 0) {
        badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

function markNotificationRead(id) { const notif = notifications.find(n => n.id === id); if (notif) { notif.read = true; renderNotificationDropdown(); updateBellBadge(); saveNotificationsToStorage(); } }
function markAllNotificationsRead() { notifications.forEach(n => n.read = true); renderNotificationDropdown(); updateBellBadge(); saveNotificationsToStorage(); addSystemEvent('All notifications marked as read', 'info', true); }
function clearAllNotifications() { if (confirm('Clear all notifications?')) { notifications = []; renderNotificationDropdown(); updateBellBadge(); saveNotificationsToStorage(); addSystemEvent('All notifications cleared', 'info', true); } }
function saveNotificationsToStorage() { try { localStorage.setItem('intellifeed_notifications', JSON.stringify(notifications)); } catch(e) {} }
function loadNotificationsFromStorage() {
    try {
        const saved = localStorage.getItem('intellifeed_notifications');
        if (saved) {
            notifications = JSON.parse(saved).map(n => ({
                ...n,
                timestamp: new Date(n.timestamp),
                timeStr: new Date(n.timestamp).toLocaleTimeString(),
                dateStr: new Date(n.timestamp).toLocaleDateString()
            }));
            notificationId = notifications.length > 0 ? Math.max(...notifications.map(n => n.id)) : 0;
            renderNotificationDropdown();
            updateBellBadge();
        }
    } catch(e) {}
}

function toggleNotificationDropdown(event) { event.stopPropagation(); const dropdown = document.getElementById('notificationDropdown'); notificationDropdownOpen = !notificationDropdownOpen; dropdown.classList.toggle('show', notificationDropdownOpen); }

function showToastNotification(message, type) { 
    document.querySelectorAll('.toast-notification').forEach(t => t.remove()); 
    const toast = document.createElement('div'); 
    toast.className = `toast-notification ${type}`; 
    toast.textContent = message; 
    document.body.appendChild(toast); 
    setTimeout(() => { 
        toast.style.opacity = '0'; 
        toast.style.transform = 'translateX(-50%) translateY(-20px)'; 
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease'; 
        setTimeout(() => toast.remove(), 300); 
    }, 3000); 
}

function addToArduinoLog(message, category = 'system') {
    const timestamp = new Date();
    activityLogEntries.unshift({ time: timestamp.toLocaleTimeString(), timestamp, message, category });
    if (activityLogEntries.length > 200) activityLogEntries.pop();
    renderActivityLogContent();
}

function renderActivityLogContent() {
    const logEl = document.getElementById('activityLogContent');
    if (!logEl) return;
    let filteredEntries = activityLogEntries;
    if (currentLogFilter !== 'all') filteredEntries = activityLogEntries.filter(entry => entry.category === currentLogFilter);
    if (filteredEntries.length === 0) { logEl.innerHTML = `<div class="log-entry"><span class="log-time">--</span><span class="log-message">No ${currentLogFilter !== 'all' ? currentLogFilter : ''} activity yet</span></div>`; return; }
    logEl.innerHTML = filteredEntries.map(entry => { let icon = ''; if (entry.category === 'food') icon = '🍖 '; else if (entry.category === 'water') icon = '💧 '; else if (entry.category === 'schedule') icon = '⏰ '; else icon = '📝 '; return `<div class="log-entry"><span class="log-time">${entry.time}</span><span class="log-message">${icon}${escapeHtml(entry.message)}</span></div>`; }).join('');
    logEl.scrollTop = 0;
}

function clearLogContent() { if (confirm('Clear all activity logs?')) { activityLogEntries = []; renderActivityLogContent(); addSystemEvent('Activity log cleared', 'info', true); } }
function filterLogs(filter) { currentLogFilter = filter; document.querySelectorAll('.log-filter-btn').forEach(btn => { btn.classList.remove('active'); if (btn.getAttribute('data-filter') === filter) btn.classList.add('active'); }); renderActivityLogContent(); }
function exportLogs() { let exportText = "IntelliFeed Activity Log Export\nGenerated: " + new Date().toLocaleString() + "\n" + "=".repeat(50) + "\n\n"; activityLogEntries.forEach(entry => { exportText += `[${entry.time}] [${entry.category.toUpperCase()}] ${entry.message}\n`; }); const blob = new Blob([exportText], { type: 'text/plain' }); const url = URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = `intellifeed_logs_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.txt`; document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url); addSystemEvent('📄 Activity log exported', 'info', true); }

function formatFullTime(time24) { if (!time24) return '--:--:--'; const parts = time24.split(':'); let hour = parseInt(parts[0]); const minute = parts[1]; const second = parts[2] || '00'; const ampm = hour >= 12 ? 'PM' : 'AM'; hour = hour % 12 || 12; return `${hour}:${minute}:${second} ${ampm}`; }
function convertTo24Hour(hour12, minute, second, ampm) { let hour24 = parseInt(hour12); if (ampm === 'PM' && hour24 !== 12) hour24 += 12; if (ampm === 'AM' && hour24 === 12) hour24 = 0; return `${String(hour24).padStart(2, '0')}:${minute}:${second}`; }
function calculateNextTriggerTime(scheduleTime) { if (!scheduleTime) return null; const now = new Date(); const [hours, minutes, seconds = '00'] = scheduleTime.split(':'); const scheduled = new Date(); scheduled.setHours(parseInt(hours), parseInt(minutes), parseInt(seconds), 0); if (scheduled <= now) scheduled.setDate(scheduled.getDate() + 1); return scheduled; }
function formatTimeWithSeconds(hour12, minute, second, ampm) { return `${hour12}:${minute}:${second} ${ampm}`; }

function getSlotIndexFromMeal(mealType) {
    switch(mealType) {
        case 'breakfast': return 0;
        case 'lunch': return 1;
        case 'dinner': return 2;
        default: return 0;
    }
}

function getDefaultTimeForMeal(mealType) {
    switch(mealType) {
        case 'breakfast': return { hour: '8', minute: '00', second: '00', ampm: 'AM' };
        case 'lunch': return { hour: '12', minute: '00', second: '00', ampm: 'PM' };
        case 'dinner': return { hour: '6', minute: '00', second: '00', ampm: 'PM' };
        default: return { hour: '8', minute: '00', second: '00', ampm: 'AM' };
    }
}

function onMealSelectChange() {
    const mealSelect = document.getElementById('mealSelect');
    if (!mealSelect) return;
    const selectedMeal = mealSelect.value;
    const defaultTime = getDefaultTimeForMeal(selectedMeal);
    
    document.getElementById('schedHour').value = defaultTime.hour;
    document.getElementById('schedMinute').value = defaultTime.minute;
    document.getElementById('schedSecond').value = defaultTime.second;
    document.getElementById('schedAmPm').value = defaultTime.ampm;
    
    const mealNames = { breakfast: '🌅 Breakfast', lunch: '☀️ Lunch', dinner: '🌙 Dinner' };
    document.getElementById('slotModalTitle').innerHTML = `➕ Add ${mealNames[selectedMeal]}`;
}

function openAddScheduleModal() {
    isEditingSchedule = false;
    currentScheduleId = null;
    editingSlotIndex = null;
    
    const mealSelect = document.getElementById('mealSelect');
    if (mealSelect) {
        mealSelect.value = 'breakfast';
        mealSelect.disabled = false;
        onMealSelectChange();
    }
    
    document.getElementById('schedPortion').value = '30';
    document.getElementById('schedCustomLabel').value = '';
    document.getElementById('schedStatus').value = '1';
    
    const deleteBtn = document.getElementById('deleteScheduleBtn');
    if (deleteBtn) deleteBtn.style.display = 'none';
    
    const oldInfo = document.querySelector('.schedule-status-info');
    if (oldInfo) oldInfo.remove();
    
    document.getElementById('scheduleModal').style.display = 'flex';
}

function openEditScheduleModal(slotIndex) {
    const schedule = slotData[slotIndex];
    if (!schedule) return;
    
    const isProcessed = scheduleLastTrigger[slotIndex] && (new Date() - new Date(scheduleLastTrigger[slotIndex]) < 86400000);
    if (isProcessed) {
        showToastNotification('❌ Cannot edit a processed schedule. Only deletion is allowed.', 'warning');
        return;
    }
    
    editingSlotIndex = slotIndex;
    isEditingSchedule = true;
    currentScheduleId = schedule.id;
    activeSlotIndex = slotIndex;
    
    const titles = ['Breakfast', 'Lunch', 'Dinner'];
    const mealNames = ['breakfast', 'lunch', 'dinner'];
    const icons = ['🌅', '☀️', '🌙'];
    
    const mealSelect = document.getElementById('mealSelect');
    if (mealSelect) {
        mealSelect.value = mealNames[slotIndex];
        mealSelect.disabled = true;
    }
    
    document.getElementById('slotModalTitle').innerHTML = `✏️ Edit ${icons[slotIndex]} ${titles[slotIndex]}`;
    
    const [hour24, minute, second = '00'] = schedule.schedule_time.split(':');
    let hour12 = parseInt(hour24) % 12 || 12;
    const ampm = parseInt(hour24) >= 12 ? 'PM' : 'AM';
    
    document.getElementById('schedHour').value = hour12.toString();
    document.getElementById('schedMinute').value = minute;
    document.getElementById('schedSecond').value = second;
    document.getElementById('schedAmPm').value = ampm;
    document.getElementById('schedPortion').value = schedule.portion;
    document.getElementById('schedCustomLabel').value = schedule.meal_label || '';
    document.getElementById('schedStatus').value = schedule.is_active || 1;
    
    const deleteBtn = document.getElementById('deleteScheduleBtn');
    if (deleteBtn) deleteBtn.style.display = 'block';
    
    const oldInfo = document.querySelector('.schedule-status-info');
    if (oldInfo) oldInfo.remove();
    
    const scheduleInfo = document.createElement('div');
    scheduleInfo.className = 'schedule-status-info';
    let statusHtml = `<strong>📋 Current Schedule:</strong><br>⏰ Time: ${formatFullTime(schedule.schedule_time)}<br>🍖 Portion: ${schedule.portion}g<br>📌 Status: ${schedule.is_active == 1 ? '✅ Active' : '⏸️ Inactive'}<br>🏷️ Label: ${schedule.meal_label || titles[slotIndex]}<br>`;
    if (scheduleLastTrigger[slotIndex]) {
        const lastTime = new Date(scheduleLastTrigger[slotIndex]);
        statusHtml += `🕐 Last triggered: ${lastTime.toLocaleString()}<br>`;
    } else {
        statusHtml += `🕐 Last triggered: Not yet<br>`;
    }
    const nextTime = calculateNextTriggerTime(schedule.schedule_time);
    if (nextTime && schedule.is_active == 1) {
        const nowTime = new Date();
        const diff = Math.floor((nextTime - nowTime) / 1000);
        let timeUntil = '';
        if (diff < 60) timeUntil = `${diff} seconds`;
        else if (diff < 3600) timeUntil = `${Math.floor(diff / 60)} minutes`;
        else if (diff < 86400) timeUntil = `${Math.floor(diff / 3600)} hours`;
        else timeUntil = `${Math.floor(diff / 86400)} days`;
        statusHtml += `⏰ Next trigger: ${nextTime.toLocaleString()} (in ${timeUntil})`;
    }
    scheduleInfo.innerHTML = statusHtml;
    document.querySelector('#scheduleModal .modal-content').appendChild(scheduleInfo);
    
    document.getElementById('scheduleModal').style.display = 'flex';
}

function deleteScheduleSlot(slotIndex) {
    const schedule = slotData[slotIndex];
    if (!schedule) return;
    
    const slotNames = ['Breakfast', 'Lunch', 'Dinner'];
    const slotName = slotNames[slotIndex];
    const scheduleTime = formatFullTime(schedule.schedule_time);
    
    if (!confirm(`Are you sure you want to delete ${slotName} schedule (${scheduleTime} - ${schedule.portion}g)?\n\nThis action cannot be undone.`)) return;
    
    currentScheduleId = schedule.id;
    activeSlotIndex = slotIndex;
    deleteSchedule();
}

async function saveSchedule() {
    const mealSelect = document.getElementById('mealSelect');
    let targetSlotIndex;
    
    if (isEditingSchedule && editingSlotIndex !== null) {
        targetSlotIndex = editingSlotIndex;
    } else {
        const selectedMeal = mealSelect ? mealSelect.value : 'breakfast';
        targetSlotIndex = getSlotIndexFromMeal(selectedMeal);
        
        if (slotData[targetSlotIndex] !== null) {
            const mealNames = { 0: 'Breakfast', 1: 'Lunch', 2: 'Dinner' };
            showToastNotification(`❌ ${mealNames[targetSlotIndex]} schedule already exists! Please edit the existing schedule instead.`, 'warning');
            return;
        }
    }
    
    const hour = document.getElementById('schedHour').value;
    const minute = document.getElementById('schedMinute').value;
    const second = document.getElementById('schedSecond').value;
    const ampm = document.getElementById('schedAmPm').value;
    const time24 = convertTo24Hour(hour, minute, second, ampm);
    const portion = parseInt(document.getElementById('schedPortion').value);
    const label = document.getElementById('schedCustomLabel').value.trim() || ['Breakfast', 'Lunch', 'Dinner'][targetSlotIndex];
    const status = parseInt(document.getElementById('schedStatus').value);
    const timeDisplay = formatTimeWithSeconds(hour, minute, second, ampm);
    const slotNames = ['Breakfast', 'Lunch', 'Dinner'];
    const slotName = slotNames[targetSlotIndex];
    
    try {
        let body;
        if (isEditingSchedule && currentScheduleId) {
            body = {
                action: 'update',
                id: currentScheduleId,
                schedule_time: time24,
                meal_label: label,
                portion: portion,
                is_active: status
            };
        } else {
            body = {
                action: 'add',
                slot_index: targetSlotIndex,
                schedule_time: time24,
                meal_label: label,
                portion: portion,
                is_active: status
            };
        }
        
        const r = await fetch(SCHED_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
            credentials: 'include'
        });
        
        const data = await r.json();
        
        if (data.success) {
            closeScheduleModal();
            await loadSchedules();
            if (isEditingSchedule) {
                addSystemEvent(`✏️ Schedule updated: ${slotName} (${label}) at ${timeDisplay} - ${portion}g [${status == 1 ? 'Active' : 'Inactive'}]`, 'success');
                showToastNotification(`✅ ${slotName} schedule updated to ${timeDisplay} (${portion}g)`, 'success');
            } else {
                addSystemEvent(`⏰ New schedule created: ${slotName} (${label}) at ${timeDisplay} - ${portion}g`, 'success');
                showToastNotification(`✅ ${slotName} schedule set for ${timeDisplay} (${portion}g)`, 'success');
            }
            if (status == 1) scheduleLastTrigger[targetSlotIndex] = null;
            if (currentPanel === 'schedule') {
                renderScheduleList();
            }
            if (mealSelect) mealSelect.disabled = false;
        } else {
            alert('Error: ' + (data.message || 'Unknown'));
            addSystemEvent(`❌ Failed to save schedule: ${data.message || 'Unknown error'}`, 'error');
        }
    } catch(e) {
        console.error('[saveSchedule] Error:', e);
        alert('Network error');
        addSystemEvent('❌ Network error while saving schedule', 'error');
    }
}

function closeScheduleModal() {
    const mealSelect = document.getElementById('mealSelect');
    if (mealSelect) mealSelect.disabled = false;
    document.getElementById('scheduleModal').style.display = 'none';
    const infoDiv = document.querySelector('.schedule-status-info');
    if (infoDiv) infoDiv.remove();
}

function renderScheduleList() {
    const panelContent = document.getElementById('panelContent');
    if (!panelContent || currentPanel !== 'schedule') return;
    
    let html = `
        <div class="panel-header">
            <span class="panel-icon">⏰</span>
            <h2>Scheduled Feedings</h2>
            <button class="badge" onclick="refreshSchedules()" style="background: var(--info); color: white;">🔄 Refresh</button>
        </div>
        <div class="schedule-header">
            <button class="add-schedule-btn" onclick="openAddScheduleModal()">
                ➕ Add Schedule
            </button>
        </div>
        <div id="scheduleListContainer" class="schedule-list">
    `;
    
    const hasAnySchedule = slotData.some(s => s !== null);
    
    if (!hasAnySchedule) {
        html += `
            <div class="empty-schedule">
                <div class="empty-schedule-icon">⏰</div>
                <h3>No Schedules Yet</h3>
                <p>Click "Add Schedule" to create a feeding schedule.</p>
            </div>
        `;
    } else {
        const pendingSchedules = [];
        const processedSchedules = [];
        
        for (let i = 0; i < slotData.length; i++) {
            const s = slotData[i];
            if (!s) continue;
            
            const isProcessed = scheduleLastTrigger[i] && (new Date() - new Date(scheduleLastTrigger[i]) < 86400000);
            
            if (isProcessed) {
                processedSchedules.push({ schedule: s, index: i, isProcessed: true });
            } else {
                pendingSchedules.push({ schedule: s, index: i, isProcessed: false });
            }
        }
        
        if (pendingSchedules.length > 0) {
            html += `<div style="margin-bottom: 20px;"><h3 style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 10px;">⏰ Pending Schedules</h3></div>`;
            pendingSchedules.forEach(({ schedule: s, index: i }) => {
                html += renderScheduleItem(s, i, false);
            });
        }
        
        if (processedSchedules.length > 0) {
            html += `<div style="margin-top: 20px; margin-bottom: 10px;"><hr style="border-color: var(--card-border);"><h3 style="font-size: 0.9rem; color: var(--text-muted); margin-top: 15px; margin-bottom: 10px;">✅ Processed Schedules (Today)</h3></div>`;
            processedSchedules.forEach(({ schedule: s, index: i }) => {
                html += renderScheduleItem(s, i, true);
            });
        }
    }
    
    html += `</div><div class="schedule-info" style="margin-top:15px;">🗑️ Auto dispose before each feed • Click Add Schedule to create new</div>`;
    panelContent.innerHTML = html;
}

function renderScheduleItem(s, i, isProcessed) {
    const mealIcon = i === 0 ? '🌅' : (i === 1 ? '☀️' : '🌙');
    const mealName = i === 0 ? 'Breakfast' : (i === 1 ? 'Lunch' : 'Dinner');
    const processedClass = isProcessed ? 'processed' : '';
    
    let statusClass = '';
    let statusText = '';
    if (isProcessed) {
        statusClass = 'processed';
        statusText = '✓ Processed';
    } else if (s.is_active == 1) {
        statusClass = 'active';
        statusText = '● Active';
    } else {
        statusClass = 'inactive';
        statusText = '○ Inactive';
    }
    
    const lastTriggerText = scheduleLastTrigger[i] 
        ? new Date(scheduleLastTrigger[i]).toLocaleString() 
        : 'Not triggered yet';
    
    return `
        <div class="schedule-item ${processedClass}" data-slot="${i}">
            <div class="schedule-info">
                <div class="meal-info">
                    <span class="meal-icon">${mealIcon}</span>
                    <div>
                        <div class="meal-name">${mealName}</div>
                        ${s.meal_label ? `<div class="meal-label">${escapeHtml(s.meal_label)}</div>` : ''}
                    </div>
                </div>
                <div class="time-display">${formatFullTime(s.schedule_time)}</div>
                <div class="portion-display">🍖 ${s.portion}g</div>
                <div class="status-badge ${statusClass}">${statusText}</div>
                <div class="last-trigger">🕐 ${lastTriggerText}</div>
            </div>
            <div class="schedule-actions">
                ${!isProcessed ? `<button class="edit-schedule-btn" onclick="openEditScheduleModal(${i})">✏️ Edit</button>` : ''}
                <button class="delete-schedule-btn" onclick="deleteScheduleSlot(${i})">🗑️ Delete</button>
            </div>
        </div>
    `;
}

// ==================== ARDUINO CONNECTION FUNCTIONS ====================
function updateConnectionStatus(isConnected, isConnecting = false) {
    const dotEl = document.getElementById('connectionDot');
    const textEl = document.getElementById('connectionText');
    
    if (!dotEl || !textEl) return;
    
    if (isConnected) {
        dotEl.className = 'live-dot connected';
        textEl.textContent = 'Connected';
        textEl.style.color = 'var(--success)';
    } else if (isConnecting) {
        dotEl.className = 'live-dot connecting';
        textEl.textContent = 'Connecting...';
        textEl.style.color = 'var(--warning)';
    } else {
        dotEl.className = 'live-dot';
        textEl.textContent = 'Disconnected';
        textEl.style.color = 'var(--danger)';
    }
}

function fetchArduinoStatus() { 
    fetch(API_URL + 'get_arduino_status.php', { 
        credentials: 'include' 
    }).then(r => r.json()).then(data => { 
        if (data.device_connected === true) { 
            if (_connState !== true) { 
                _connState = true; 
                addSystemEvent('✅ Arduino connected and online', 'success'); 
            } 
            
            const dotEl = document.getElementById('connectionDot'); 
            const textEl = document.getElementById('connectionText'); 
            if (dotEl) dotEl.className = 'live-dot connected'; 
            if (textEl) {
                textEl.textContent = 'Connected';
                textEl.style.color = '';
            }
            
            const updateEl = document.getElementById('lastUpdate'); 
            if (updateEl) updateEl.textContent = new Date().toLocaleTimeString(); 
            const pendingEl = document.getElementById('pendingCommands'); 
            if (pendingEl) pendingEl.textContent = data.pending_commands || 0; 
            updateWeightDisplay(parseFloat(data.weight || 0)); 
            updateWaterDisplay(parseFloat(data.water_weight || 0)); 
            updateWaterStorage(parseFloat(data.wstorage_pct || 0)); 
            if (data.last_feeding_time) { 
                const el = document.getElementById('lastFeedingPanel'); 
                if (el) el.textContent = new Date(data.last_feeding_time).toLocaleTimeString(); 
            } 
            const feedEl = document.getElementById('feedingStatusPanel'); 
            if (feedEl) feedEl.innerHTML = data.feeding ? '🔴 Feeding...' : '● Idle'; 
            currentPlateInside = data.plate_inside || false; 
            currentNoPlate = data.no_plate || false; 
            checkFoodEmpty(data); 
            checkNoPlate(currentNoPlate); 
            updateFeedButtonLabel(); 
            
        } else { 
            if (_connState !== false) { 
                _connState = false; 
                addSystemEvent('⚠️ Arduino is offline or not responding. Please check the device.', 'warning'); 
            } 
            
            const dotEl = document.getElementById('connectionDot'); 
            const textEl = document.getElementById('connectionText'); 
            if (dotEl) dotEl.className = 'live-dot';
            if (textEl) {
                textEl.textContent = 'Disconnected';
                textEl.style.color = '#EF4444';
            }
            
            const updateEl = document.getElementById('lastUpdate'); 
            if (updateEl) updateEl.textContent = '--'; 
            updateWeightDisplay(0); 
            updateWaterDisplay(0); 
            updateWaterStorage(0); 
        } 
    }).catch((err) => { 
        console.error('[fetchArduinoStatus] Network Error:', err); 
        
        _connState = false;
        
        const dotEl = document.getElementById('connectionDot'); 
        const textEl = document.getElementById('connectionText'); 
        if (dotEl) dotEl.className = 'live-dot'; 
        if (textEl) {
            textEl.textContent = 'Disconnected';
            textEl.style.color = '#EF4444';
        }
    }); 
}

function checkFoodEmpty(data) { 
    const banner = document.getElementById('foodEmptyBanner'); 
    const statEl = document.getElementById('foodLevelPanel'); 
    if (data.food_empty) { 
        if (!foodEmptyDismissed && banner) banner.style.display = 'flex'; 
        if (statEl) { 
            statEl.textContent = 'EMPTY'; 
            statEl.style.color = '#EF4444'; 
        } 
        if (!_foodEmptyState) { 
            _foodEmptyState = true; 
            addSystemEvent('⚠️ FOOD STORAGE EMPTY! Please refill food storage immediately.', 'error'); 
        } 
    } else { 
        if (banner) banner.style.display = 'none'; 
        if (statEl) { 
            statEl.textContent = 'OK'; 
            statEl.style.color = 'var(--success)'; 
        } 
        if (_foodEmptyState) { 
            _foodEmptyState = false; 
            foodEmptyDismissed = false; 
            addSystemEvent('✅ Food storage refilled and available.', 'success'); 
        } 
    } 
}

function dismissFoodEmptyWarning() { 
    const banner = document.getElementById('foodEmptyBanner'); 
    if (banner) banner.style.display = 'none'; 
    foodEmptyDismissed = true; 
    addSystemEvent('⚠️ Food empty warning dismissed by user.', 'info', true); 
}

function checkWaterContainer(waterWeight) {
    const isMissing = (waterWeight < WATER_PLATE_MIN_GRAMS);
    if (isMissing) {
        if (_waterContainerOK) {
            _waterContainerOK = false;
            waterContainerMissing = true;
            addWaterNotification('💧 WATER CONTAINER MISSING! Please place a water container on the scale.', 'error');
            addToArduinoLog('💧 WATER CONTAINER MISSING! Please place a water container on the scale.', 'water');
        }
    } else {
        if (!_waterContainerOK) {
            _waterContainerOK = true;
            waterContainerMissing = false;
            waterContainerMissingDismissed = false;
            addWaterNotification('✅ Water container detected. Water system ready.', 'success');
            addToArduinoLog('✅ Water container detected. Water system ready.', 'water');
        }
    }
}

function checkNoPlate(isNoPlate) { 
    const platePosEl = document.getElementById('platePositionPanel'); 
    if (isNoPlate) { 
        if (platePosEl) platePosEl.innerHTML = '<span style="color:#EF4444;">❌ Empty</span>'; 
        if (!_noPlateState) { 
            _noPlateState = true; 
            addSystemEvent('🍽️ NO PLATES IN DISPENSER! Please refill the plate dispenser.', 'warning'); 
        } 
    } else { 
        if (platePosEl) { 
            if (currentPlateInside) platePosEl.innerHTML = '<span style="color:#10B981;">✅ Available</span>'; 
            else platePosEl.innerHTML = '<span style="color:var(--text-muted);">--</span>'; 
        } 
        if (_noPlateState) { 
            _noPlateState = false; 
            addSystemEvent('✅ Plate dispenser restocked with plates.', 'success'); 
        } 
    } 
}

function checkWaterStorageLow(pct) {
    pct = parseFloat(pct) || 0;
    if (pct <= 20 && !_waterLowState) {
        _waterLowState = true;
        addWaterNotification(`🚱 WATER STORAGE LOW! Only ${pct.toFixed(0)}% remaining. Please refill water tank.`, 'warning');
        addToArduinoLog(`🚱 WATER STORAGE LOW! Only ${pct.toFixed(0)}% remaining. Please refill water tank.`, 'water');
    } else if (pct > 20 && _waterLowState) {
        _waterLowState = false;
        addWaterNotification(`✅ Water storage level restored to ${pct.toFixed(0)}%.`, 'success');
        addToArduinoLog(`✅ Water storage level restored to ${pct.toFixed(0)}%.`, 'water');
    }
}

function toggleDropdownMenu(event) { 
    event.stopPropagation(); 
    const menu = document.getElementById('dropdownMenu'); 
    dropdownOpen = !dropdownOpen; 
    menu.classList.toggle('show', dropdownOpen); 
    
    if (notificationDropdownOpen) {
        document.getElementById('notificationDropdown').classList.remove('show');
        notificationDropdownOpen = false;
    }
}

function selectMenuItem(panelName) { 
    document.getElementById('dropdownMenu').classList.remove('show'); 
    dropdownOpen = false; 
    currentPanel = panelName; 
    document.querySelectorAll('.dropdown-item').forEach(item => item.classList.remove('active')); 
    const activeItem = document.querySelector(`.dropdown-item[data-panel="${panelName}"]`); 
    if (activeItem) activeItem.classList.add('active'); 
    switch(panelName) { 
        case 'liveStatus': renderLiveStatusPanel(); break; 
        case 'recentFeedings': renderRecentFeedingsPanel(); break; 
        case 'activityLog': renderActivityLogPanel(); break; 
        case 'feed': renderFeedPanel(); break; 
        case 'history': loadFeedingHistory('today'); break; 
        case 'water': renderWaterPanel(); break; 
        case 'schedule': renderSchedulePanel(); break; 
        case 'journal': renderJournalPanel(); break; 
        case 'watch': renderWatchPanel(); break; 
        case 'manual': renderManualPanel(); break; 
        case 'tips': renderTipsPanel(); break; 
        default: renderLiveStatusPanel(); break; 
    } 
}

function renderLiveStatusPanel() { 
    const panelContent = document.getElementById('panelContent'); 
    if (!panelContent) return; 
    panelContent.innerHTML = `<div class="panel-header"><span class="panel-icon">📊</span><h2>Live Status</h2><button class="badge" onclick="refreshLiveStatus()" style="background: var(--info); color: white;">🔄 Refresh</button></div><div class="live-status-grid"><div class="live-status-item"><div class="live-status-label">Last Feeding</div><div class="live-status-value" id="lastFeedingPanel">--</div></div><div class="live-status-item"><div class="live-status-label">Weight on Plate</div><div class="live-status-value" id="weightLevelPanel">0.0 g</div></div><div class="live-status-item"><div class="live-status-label">Feeding Status</div><div class="live-status-value" id="feedingStatusPanel">Idle</div></div><div class="live-status-item"><div class="live-status-label">Plate Dispenser</div><div class="live-status-value" id="platePositionPanel">--</div></div><div class="live-status-item"><div class="live-status-label">Food Storage</div><div class="live-status-value" id="foodLevelPanel">OK</div></div><div class="live-status-item"><div class="live-status-label">Water Storage</div><div class="live-status-value" id="wStoragePanel">--%</div></div></div>`; 
}

function refreshLiveStatus() { fetchArduinoStatus(); }

function renderRecentFeedingsPanel() { 
    const panelContent = document.getElementById('panelContent'); 
    if (!panelContent) return; 
    panelContent.innerHTML = `<div class="panel-header"><span class="panel-icon">📜</span><h2>Recent Feedings</h2><div><button class="history-filter-btn" onclick="loadRecentFeedingsContent('today')">Today</button><button class="history-filter-btn" onclick="loadRecentFeedingsContent('week')">Week</button></div></div><div id="recentFeedingsContent"><div class="empty-feedings">Loading...</div></div><div class="view-all-link"><button onclick="selectMenuItem('history')">View Full History →</button></div>`; 
    loadRecentFeedingsContent(recentFilter); 
}

async function loadRecentFeedingsContent(filter = 'today') { 
    recentFilter = filter; 
    try { 
        let url = HISTORY_API_URL + '?action=get&user_id=<?php echo $userId; ?>'; 
        if (filter === 'today') url += '&range=today'; 
        else if (filter === 'week') url += '&range=week'; 
        const response = await fetch(url, { credentials: 'include' }); 
        const data = await response.json(); 
        const container = document.getElementById('recentFeedingsContent'); 
        if (!container) return; 
        if (data.success && data.history && data.history.length > 0) { 
            const limitedHistory = data.history.slice(0, 10); 
            let html = `<table class="recent-feedings-table"><thead><tr><th>Time</th><th>Portion</th><th>Eaten</th></tr></thead><tbody>`; 
            limitedHistory.forEach(record => { 
                const date = new Date(record.created_at); 
                const timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); 
                const consumed = record.weight_final ? (record.portion - record.weight_final).toFixed(1) : record.portion; 
                html += `<tr><td>${timeStr}</td><td><span class="portion-badge">${record.portion}g</span></td><td>${consumed > 0 ? '▼ ' + consumed + 'g' : '0g'}</td></tr>`; 
            }); 
            html += `</tbody></table>`; 
            container.innerHTML = html; 
        } else { 
            container.innerHTML = '<div class="empty-feedings">No feeding records yet</div>'; 
        } 
    } catch (error) { 
        const container = document.getElementById('recentFeedingsContent'); 
        if (container) container.innerHTML = '<div class="empty-feedings">Unable to load</div>'; 
    } 
}

function renderActivityLogPanel() { 
    const panelContent = document.getElementById('panelContent'); 
    if (!panelContent) return; 
    panelContent.innerHTML = `<div class="panel-header"><span class="panel-icon">📝</span><h2>Activity Log</h2><div style="display: flex; gap: 5px;"><button class="badge" onclick="exportLogs()" style="background: var(--info); color: white;">📄 Export</button><button class="badge" onclick="clearLogContent()" style="background: var(--danger); color: white;">🗑️ Clear</button></div></div><div class="log-filters" style="display: flex; gap: 8px; margin-bottom: 15px; flex-wrap: wrap; border-bottom: 1px solid var(--card-border); padding-bottom: 12px;"><button class="log-filter-btn ${currentLogFilter === 'all' ? 'active' : ''}" data-filter="all" onclick="filterLogs('all')">📋 All Activities</button><button class="log-filter-btn ${currentLogFilter === 'food' ? 'active' : ''}" data-filter="food" onclick="filterLogs('food')">🍖 Food & Feeding</button><button class="log-filter-btn ${currentLogFilter === 'water' ? 'active' : ''}" data-filter="water" onclick="filterLogs('water')">💧 Water System</button><button class="log-filter-btn ${currentLogFilter === 'schedule' ? 'active' : ''}" data-filter="schedule" onclick="filterLogs('schedule')">⏰ Schedules</button></div><div class="log-entries" id="activityLogContent"><div class="log-entry"><span class="log-time">Start</span><span class="log-message">🚀 System initialized and ready</span></div></div><div style="margin-top: 15px; text-align: center; font-size: 0.65rem; color: var(--text-muted); display: flex; justify-content: space-between;"><span>📊 ${activityLogEntries.length} total activities</span><span>🔍 Filter: ${currentLogFilter === 'all' ? 'All' : currentLogFilter.charAt(0).toUpperCase() + currentLogFilter.slice(1)}</span></div>`; 
    renderActivityLogContent(); 
}

function renderWaterPanel() {
    const panelContent = document.getElementById('panelContent');
    if (!panelContent) return;
    
    panelContent.innerHTML = `
        <div class="panel-header">
            <span class="panel-icon">💧</span>
            <h2>Water System</h2>
            <button class="badge" onclick="refreshWaterData()" style="background: var(--info); color: white;">🔄 Refresh</button>
        </div>
        
        <div class="water-status-grid">
            <div class="card">
                <div class="card-header">
                    <span style="font-size: 1.5rem;">💧</span>
                    <h3>Water Plate</h3>
                </div>
                <div class="water-widget">
                    <div class="water-widget-row">
                        <div>
                            <div class="water-label" style="font-size: 0.7rem; color: var(--text-muted);">Current Weight</div>
                            <div class="water-value" style="font-size: 1.2rem; font-weight: 600;">
                                <span id="waterWeightDisplay">0.0</span> g
                            </div>
                        </div>
                        <div class="water-bar-track">
                            <div class="water-bar-fill" id="waterLevelFill"></div>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 10px; font-size: 0.7rem; color: var(--text-muted); text-align: center;">
                    ${waterContainerMissing ? '⚠️ No container detected' : '✓ Container present'}
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <span style="font-size: 1.5rem;">🗄️</span>
                    <h3>Water Storage Tank</h3>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <div style="flex: 1; background: var(--badge-bg); border-radius: 15px; height: 20px; overflow: hidden;">
                            <div id="wStorageBar" style="height: 100%; width: 0%; background: #3B82F6; transition: width 0.5s;"></div>
                        </div>
                        <span id="wStorageStat" style="font-size: 0.9rem; font-weight: 600;">0%</span>
                    </div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); text-align: center;">
                        <span id="waterStorageStatus">Status: Normal</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header" style="justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 1.2rem;">🔔</span>
                    <h3>Water Notifications</h3>
                    <span id="waterBadge" class="water-badge" style="display: none; background: var(--danger); color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.6rem; align-items: center; justify-content: center;">0</span>
                </div>
                <button class="badge" onclick="clearAllWaterNotifications()" style="background: var(--danger); color: white;">🗑️ Clear All</button>
            </div>
            <div class="water-notifications-list" id="waterNotificationsList">
                <div class="empty-notifications-dropdown">💧 No water notifications yet</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span style="font-size: 1.2rem;">ℹ️</span>
                <h3>Water System Info</h3>
            </div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); line-height: 1.6;">
                <p>• Water container should weigh at least ${WATER_PLATE_MIN_GRAMS}g to be detected</p>
                <p>• Water storage level is monitored in real-time</p>
                <p>• Low water warning triggers at 20% remaining</p>
                <p>• All water-related notifications appear only in this panel</p>
            </div>
        </div>
    `;
    
    updateWaterDisplay(_lastKnownWaterWeight || 0);
    updateWaterStorage(_lastKnownWaterStoragePct || 0);
    renderWaterNotifications();
    updateWaterBadge();
}

function refreshWaterData() {
    fetchArduinoStatus();
}

let _lastKnownWaterWeight = 0;
let _lastKnownWaterStoragePct = 0;

function updateWaterDisplay(weight) {
    _lastKnownWaterWeight = weight;
    const display = document.getElementById('waterWeightDisplay');
    const fill = document.getElementById('waterLevelFill');
    if (display) display.textContent = weight.toFixed(1);
    if (fill) fill.style.height = Math.min(100, (weight / 500) * 100) + '%';
    checkWaterContainer(weight);
}

function updateWaterStorage(pct) {
    _lastKnownWaterStoragePct = pct;
    pct = parseFloat(pct) || 0;
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    set('wStorageStat', pct.toFixed(0) + '%');
    set('wStoragePanel', pct.toFixed(0) + '%');
    const bar = document.getElementById('wStorageBar');
    if (bar) bar.style.width = Math.min(100, pct) + '%';
    const statusEl = document.getElementById('waterStorageStatus');
    if (statusEl) {
        if (pct <= 20) statusEl.innerHTML = '⚠️ LOW WATER - Refill soon!';
        else if (pct <= 50) statusEl.innerHTML = '⚠️ Water level decreasing';
        else statusEl.innerHTML = '✓ Normal operation';
    }
    checkWaterStorageLow(pct);
}

function renderSchedulePanel() { 
    renderScheduleList(); 
}

function renderFeedPanel() { 
    const panelContent = document.getElementById('panelContent'); 
    if (!panelContent) return; 
    panelContent.innerHTML = `<div class="panel-header"><span class="panel-icon">🍖</span><h2>Feed Now</h2></div><div class="card"><button class="btn-feed" onclick="openPlateModal()" id="feedNowBtn"><span>🍖</span><span>Feed Now</span><span class="btn-portion" id="currentPortion">30g</span></button><div class="portion-selector"><span class="selector-label">Portion Size</span><input type="range" id="portionSlider" min="10" max="500" value="30" step="5"><div style="display:flex;justify-content:space-between;font-size:0.65rem;margin-top:5px;"><span>10g</span><span>100g</span><span>200g</span><span>350g</span><span>500g</span></div><div style="text-align:center;margin-top:8px;"><span id="portionValue">30 grams</span></div></div><button class="btn-reset" onclick="resetCounter()"><span>🔄</span> Reset Counter</button><button class="btn-reset" onclick="sendDisposeCommand()" style="margin-top:8px;"><span>🗑️</span> Dispose Plate</button></div><div class="card"><div class="card-header"><span class="card-icon">⚖️</span><h3>Current Plate Weight</h3></div><div class="weight-visual"><div class="weight-tank"><div class="weight-fill" id="weightFillPanel"></div></div><div><div style="font-size:1.2rem;"><span id="currentWeightPanel">0.0</span> g</div><button class="btn-tare" onclick="tareScale()">⚖️ Tare Scale</button></div></div></div>`; 
    const slider = document.getElementById('portionSlider'); 
    if (slider) slider.addEventListener('input', function(e) { 
        document.getElementById('portionValue').textContent = e.target.value + ' grams'; 
        updateFeedButtonLabel(); 
    }); 
    updateFeedButtonLabel(); 
    updateCurrentValues(); 
}

function renderJournalPanel() { 
    const panelContent = document.getElementById('panelContent'); 
    if (!panelContent) return; 
    panelContent.innerHTML = `<div class="panel-header"><span class="panel-icon">📔</span><h2>Pet Journal</h2><div style="display:flex;gap:10px;"><button class="badge" onclick="openJournalModal()" style="background:var(--success);color:white;">+ Add Note</button><button class="badge" onclick="openTrashModal()" style="background:var(--danger);color:white;">🗑️ Trash</button></div></div><div class="journal-stats"><div class="stat-pill">📝 Total Notes: <span id="journalTotalCount">0</span></div><div class="stat-pill">🐱 Cats: <span id="journalCatCount">0</span></div><div class="stat-pill">🐶 Dogs: <span id="journalDogCount">0</span></div><div class="stat-pill">📅 Last Entry: <span id="journalLastDate">Never</span></div></div><div class="journal-entries" id="journalEntries"><div class="loading-message">Loading journal entries...</div></div>`; 
    loadJournalEntries(); 
}

function renderWatchPanel() { 
    const panelContent = document.getElementById('panelContent'); 
    if (!panelContent) return; 
    panelContent.innerHTML = `<div class="panel-header"><span class="panel-icon">📹</span><h2>Watch My Pet</h2></div><div style="text-align:center;padding:40px;"><a href="camera.html" style="display:inline-flex;align-items:center;gap:12px;background:linear-gradient(135deg,var(--primary-accent),var(--secondary-accent));color:white;text-decoration:none;padding:14px 28px;border-radius:50px;font-size:0.9rem;"><span>📹</span> Launch AI Camera</a><p style="margin-top:20px;font-size:0.75rem;color:var(--text-muted);">Click to open AI camera for pet detection and recognition</p></div>`; 
}

function renderManualPanel() { 
    const panelContent = document.getElementById('panelContent'); 
    if (!panelContent) return; 
    panelContent.innerHTML = `<div class="panel-header"><span class="panel-icon">📖</span><h2>User Manual</h2></div><div class="card"><h3>🔰 Getting Started</h3><ol style="padding-left:20px;"><li>Power on ESP32 device</li><li>Connect to WiFi automatically</li><li>Login to dashboard</li><li>Check green dot for connection</li></ol></div><div class="card"><h3>🍖 How to Feed</h3><ol style="padding-left:20px;"><li>Set portion with slider</li><li>Choose Stock or New Plate</li><li>Click Feed Now</li><li>Use Dispose Plate to remove old food</li></ol></div><div class="card"><h3>⏰ Setting Schedules</h3><ol style="padding-left:20px;"><li>Click any schedule slot</li><li>Set time and portion</li><li>Save to activate</li></ol></div>`; 
}

function renderTipsPanel() { 
    const panelContent = document.getElementById('panelContent'); 
    if (!panelContent) return; 
    panelContent.innerHTML = `<div class="panel-header"><span class="panel-icon">💡</span><h2>Feeding Tips</h2></div><div class="card"><h3>🐱 Cat Feeding</h3><ul style="padding-left:20px;"><li>Adult cats: 20-30g per kg body weight daily</li><li>2-3 meals per day</li><li>Always fresh water</li><li>Avoid: onions, garlic, chocolate, grapes</li></ul></div><div class="card"><h3>🐶 Dog Feeding</h3><ul style="padding-left:20px;"><li>Small dogs (2-10kg): 40-100g/day</li><li>Medium dogs (10-25kg): 100-250g/day</li><li>Large dogs (25-45kg): 250-400g/day</li><li>2 meals per day for adults</li></ul></div><div class="card"><h3>💡 General Tips</h3><ul style="padding-left:20px;"><li>Monitor your pet's weight regularly</li><li>Feed at consistent times daily</li><li>Always provide fresh, clean water</li><li>Consult veterinarian for specific dietary needs</li></ul></div>`; 
}

function loadFeedingHistory(filter = 'today') { 
    currentFilter = filter; 
    const panelContent = document.getElementById('panelContent'); 
    if (!panelContent) return; 
    panelContent.innerHTML = `<div class="panel-header"><span class="panel-icon">📊</span><h2>Feeding History</h2><button class="badge" onclick="loadFeedingHistory('${filter}')" style="background:var(--info);color:white;">🔄 Refresh</button></div><div class="history-filters" style="display:flex;gap:5px;margin-bottom:15px;flex-wrap:wrap;"><button class="history-filter-btn ${filter==='today'?'active':''}" onclick="loadFeedingHistory('today')">Today</button><button class="history-filter-btn ${filter==='week'?'active':''}" onclick="loadFeedingHistory('week')">Week</button><button class="history-filter-btn ${filter==='month'?'active':''}" onclick="loadFeedingHistory('month')">Month</button><button class="history-filter-btn ${filter==='all'?'active':''}" onclick="loadFeedingHistory('all')">All</button></div><div id="historyContent"><div class="empty-feedings">Loading...</div></div>`; 
    fetch(HISTORY_API_URL + '?action=get&user_id=<?php echo $userId; ?>&range=' + filter, { credentials: 'include' }).then(r => r.json()).then(data => { 
        const container = document.getElementById('historyContent'); 
        if (!container) return; 
        if (data.success) { 
            let html = `<div style="display:flex;gap:10px;margin-bottom:15px;flex-wrap:wrap;"><div class="stat-pill">📊 Total: ${data.stats.total_feedings || 0}</div><div class="stat-pill">🍖 Food: ${(data.stats.total_grams || 0).toFixed(0)}g</div><div class="stat-pill">📈 Avg: ${(data.stats.avg_portion || 0).toFixed(0)}g</div></div>`; 
            if (data.history && data.history.length > 0) { 
                html += `<div style="overflow-x:auto;"><table style="width:100%;border-collapse:collapse;font-size:0.75rem;"><thead><tr style="border-bottom:1px solid var(--card-border);"><th style="padding:8px;text-align:left;">Date & Time</th><th style="padding:8px;text-align:left;">Type</th><th style="padding:8px;text-align:left;">Portion</th><th style="padding:8px;text-align:left;">Eaten</th></tr></thead><tbody>`; 
                data.history.forEach(record => { 
                    const dateStr = new Date(record.created_at).toLocaleString(); 
                    const icon = record.plate_type === 'new' ? '🍽️' : '🫙'; 
                    const consumed = record.weight_final ? (record.portion - record.weight_final).toFixed(1) : record.portion; 
                    html += `<tr><td style="padding:6px;">${dateStr}</td><td style="padding:6px;">${icon}</td><td style="padding:6px;"><span class="portion-badge">${record.portion}g</span></td><td style="padding:6px;">${consumed > 0 ? '▼ '+consumed+'g' : '0g'}</td></tr>`; 
                }); 
                html += `</tbody>}</div>`; 
            } else { 
                html += `<div class="empty-feedings">🍽️ No feeding records found</div>`; 
            } 
            container.innerHTML = html; 
        } else { 
            container.innerHTML = `<div class="empty-feedings">⚠️ Unable to load history</div>`; 
        } 
    }).catch(() => { 
        const container = document.getElementById('historyContent'); 
        if (container) container.innerHTML = '<div class="empty-feedings">⚠️ Unable to load history</div>'; 
    }); 
}

async function loadJournalEntries() { 
    try { 
        const r = await fetch(JOURNAL_API_URL + '?action=get_all', { credentials: 'include' }); 
        const data = await r.json(); 
        if (data.success) { 
            renderJournalEntries(data.entries); 
            updateJournalStats(data.entries); 
        } else { 
            const el = document.getElementById('journalEntries'); 
            if (el) el.innerHTML = '<div class="empty-state"><p>No journal entries yet</p><button class="badge" onclick="openJournalModal()" style="margin-top:10px;">+ Add Your First Note</button></div>'; 
        } 
    } catch(e) { 
        const el = document.getElementById('journalEntries'); 
        if (el) el.innerHTML = '<div class="empty-state"><p>Error loading entries</p></div>'; 
    } 
}

function renderJournalEntries(entries) { 
    const container = document.getElementById('journalEntries'); 
    if (!container) return; 
    if (!entries || entries.length === 0) { 
        container.innerHTML = '<div class="empty-state"><p>No journal entries yet</p><button class="badge" onclick="openJournalModal()" style="margin-top:10px;">+ Add Your First Note</button></div>'; 
        return; 
    } 
    container.innerHTML = entries.map(e => { 
        const d = new Date(e.created_at); 
        const time = d.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true }); 
        const icon = e.pet_type === 'cat' ? '🐱' : '🐶'; 
        const isDeleted = e.deleted_at !== null; 
        return `<div class="journal-note ${isDeleted ? 'deleted' : ''}" data-id="${e.id}"><div class="note-header"><span class="note-pet-icon">${icon}</span><span class="note-pet-name">${escapeHtml(e.pet_name)}</span>${e.note_title ? `<span class="note-title">${escapeHtml(e.note_title)}</span>` : ''}<span class="note-time">🕐 ${time}</span></div><div class="note-content">${escapeHtml(e.note_content)}</div><div class="note-footer"><span class="note-type">${icon} ${e.pet_type}</span><div class="note-actions">${!isDeleted ? `<button class="note-action-btn delete" onclick="deleteJournalEntry(${e.id})" title="Move to trash">🗑️</button>` : ''}${isDeleted ? `<button class="note-action-btn restore" onclick="restoreJournalEntry(${e.id})" title="Restore">↩️</button>` : ''}${isDeleted ? `<button class="note-action-btn permanent-delete" onclick="permanentDeleteJournalEntry(${e.id})" title="Delete permanently">❌</button>` : ''}</div></div></div>`; 
    }).join(''); 
}

function updateJournalStats(entries) { 
    if (!entries) return; 
    const active = entries.filter(e => !e.deleted_at); 
    const el = (id, val) => { const e = document.getElementById(id); if (e) e.textContent = val; }; 
    el('journalTotalCount', active.length); 
    el('journalCatCount', active.filter(e => e.pet_type === 'cat').length); 
    el('journalDogCount', active.filter(e => e.pet_type === 'dog').length); 
    el('journalLastDate', active.length ? new Date(active[0].created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : 'Never'); 
}

function openJournalModal() { document.getElementById('journalModal').style.display = 'flex'; document.getElementById('journalPetName').focus(); }
function closeJournalModal() { document.getElementById('journalModal').style.display = 'none'; ['journalPetName','journalTitle','journalNote'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; }); }

async function saveJournalEntry() { 
    const petName = document.getElementById('journalPetName').value.trim(); 
    const petType = document.getElementById('journalPetType').value; 
    const noteTitle = document.getElementById('journalTitle').value.trim(); 
    const noteContent = document.getElementById('journalNote').value.trim(); 
    if (!petName) { alert('Please enter a pet name'); return; } 
    if (!noteContent) { alert('Please enter a note'); return; } 
    if (noteContent.length > MAX_NOTE_LENGTH) { alert('Note too long'); return; } 
    try { 
        const r = await fetch(JOURNAL_API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'add', petName, petType, noteTitle, noteContent }), credentials: 'include' }); 
        const data = await r.json(); 
        if (data.success) { 
            closeJournalModal(); 
            await loadJournalEntries(); 
            addSystemEvent(`📔 Journal entry saved for ${petName}: "${noteContent.substring(0, 50)}${noteContent.length > 50 ? '...' : ''}"`, 'success'); 
        } else { 
            alert('Error: ' + (data.message || 'Unknown')); 
        } 
    } catch(e) { 
        alert('Network error. Try again.'); 
    } 
}

async function deleteJournalEntry(id) { 
    if (!confirm('Move this note to trash?')) return; 
    try { 
        const r = await fetch(JOURNAL_API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete', journalId: id }), credentials: 'include' }); 
        const data = await r.json(); 
        if (data.success) { 
            await loadJournalEntries(); 
            addSystemEvent('🗑️ Journal note moved to trash', 'info'); 
        } else { 
            alert('Error: ' + (data.message || 'Unknown')); 
        } 
    } catch(e) { 
        alert('Network error.'); 
    } 
}

async function restoreJournalEntry(id) { 
    try { 
        const r = await fetch(JOURNAL_API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'restore', journalId: id }), credentials: 'include' }); 
        const data = await r.json(); 
        if (data.success) { 
            await loadJournalEntries(); 
            addSystemEvent('✅ Journal entry restored from trash', 'success'); 
        } else { 
            alert('Error: ' + (data.message || 'Unknown')); 
        } 
    } catch(e) { 
        alert('Network error.'); 
    } 
}

async function permanentDeleteJournalEntry(id) { 
    if (!confirm('Permanently delete? This cannot be undone.')) return; 
    try { 
        const r = await fetch(JOURNAL_API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'permanent_delete', journalId: id }), credentials: 'include' }); 
        const data = await r.json(); 
        if (data.success) { 
            await loadJournalEntries(); 
            addSystemEvent('🗑️ Journal note permanently deleted', 'warning'); 
        } else { 
            alert('Error: ' + (data.message || 'Unknown')); 
        } 
    } catch(e) { 
        alert('Network error.'); 
    } 
}

async function openTrashModal() { document.getElementById('trashModal').style.display = 'flex'; await loadTrash(); }
function closeTrashModal() { document.getElementById('trashModal').style.display = 'none'; }

async function loadTrash() { 
    try { 
        const r = await fetch(JOURNAL_API_URL + '?action=get_trash', { credentials: 'include' }); 
        const data = await r.json(); 
        const container = document.getElementById('trashEntries'); 
        if (!container) return; 
        if (data.success && data.entries && data.entries.length > 0) { 
            container.innerHTML = data.entries.map(e => { 
                const icon = e.pet_type === 'cat' ? '🐱' : '🐶'; 
                const time = new Date(e.deleted_at || e.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); 
                return `<div class="journal-note deleted" data-id="${e.id}"><div class="note-header"><span class="note-pet-icon">${icon}</span><span class="note-pet-name">${escapeHtml(e.pet_name)}</span><span class="note-time">🗑️ ${time}</span></div><div class="note-content">${escapeHtml(e.note_content.substring(0,100))}${e.note_content.length>100?'...':''}</div><div class="note-footer"><span class="note-type">${icon} ${e.pet_type}</span><div class="note-actions"><button class="note-action-btn restore" onclick="restoreJournalEntry(${e.id})" title="Restore">↩️</button><button class="note-action-btn permanent-delete" onclick="permanentDeleteJournalEntry(${e.id})" title="Delete permanently">❌</button></div></div></div>`; 
            }).join(''); 
        } else { 
            container.innerHTML = '<div class="empty-state"><p>Trash is empty</p></div>'; 
        } 
    } catch(e) { 
        const container = document.getElementById('trashEntries'); 
        if (container) container.innerHTML = '<div class="empty-state"><p>Error loading trash</p></div>'; 
    } 
}

async function emptyTrash() { 
    if (!confirm('Permanently delete ALL items in trash?')) return; 
    try { 
        const r = await fetch(JOURNAL_API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'empty_trash' }), credentials: 'include' }); 
        const data = await r.json(); 
        if (data.success) { 
            await loadTrash(); 
            await loadJournalEntries(); 
            addSystemEvent('🗑️ Trash emptied permanently', 'warning'); 
        } else { 
            alert('Error: ' + (data.message || 'Unknown')); 
        } 
    } catch(e) { 
        alert('Network error.'); 
    } 
}

function toggleTheme() { 
    const next = document.body.getAttribute('data-theme') === 'light' ? 'dark' : 'light'; 
    document.body.setAttribute('data-theme', next); 
    localStorage.setItem('theme', next); 
}

function loadThemePreference() { 
    const t = localStorage.getItem('theme') || 'light'; 
    document.body.setAttribute('data-theme', t); 
}

function updateFeedButtonLabel() { 
    const portion = document.getElementById('portionSlider')?.value || 30; 
    const portionSpan = document.getElementById('currentPortion'); 
    if (portionSpan) portionSpan.textContent = `${portion}g`; 
    const btn = document.getElementById('feedNowBtn'); 
    if (!btn) return; 
    const foodWeight = parseFloat(document.getElementById('currentWeightPanel')?.textContent) || 0; 
    const autoNew = (foodWeight < 5.5) || currentNoPlate || !currentPlateInside; 
    if (autoNew) { 
        btn.innerHTML = `<span class="btn-icon">🍽️</span><span>Feed Now</span><span class="btn-portion" style="background:#F59E0B;color:white;">${portion}g · Auto New</span>`; 
    } else { 
        btn.innerHTML = `<span class="btn-icon">🍖</span><span>Feed Now</span><span class="btn-portion">${portion}g</span>`; 
    } 
}

function updateWeightDisplay(weight) { 
    _lastKnownWeight = weight; 
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; }; 
    const setH = (id, val) => { const el = document.getElementById(id); if (el) el.style.height = val; }; 
    set('currentWeightDisplay', weight.toFixed(1)); 
    set('currentWeightStat', weight.toFixed(1)); 
    set('weightLevelText', weight.toFixed(1) + ' g'); 
    set('currentWeightPanel', weight.toFixed(1)); 
    set('weightLevelPanel', weight.toFixed(1) + ' g'); 
    const pct = Math.min(100, (weight / 500) * 100) + '%'; 
    setH('weightFill', pct); 
    setH('weightFillPanel', pct); 
}

function updateCurrentValues() { 
    const weight = _lastKnownWeight; 
    const panelWeight = document.getElementById('currentWeightPanel'); 
    const panelFill = document.getElementById('weightFillPanel'); 
    if (panelWeight) panelWeight.textContent = weight.toFixed(1); 
    if (panelFill) panelFill.style.height = Math.min(100, (weight / 500) * 100) + '%'; 
}

function tareScale() { 
    fetch(ESP32_BASE + '/tare').then(() => { 
        addSystemEvent('⚖️ Scale tared successfully', 'success'); 
    }).catch(() => addSystemEvent('❌ Tare command failed! Check Arduino connection.', 'error')); 
}

function resetCounter() { 
    if (confirm('Reset all counters?')) { 
        addSystemEvent('🔄 Feed counters have been reset', 'info'); 
    } 
}

async function saveFeedingRecord(plateType, portion, weightAfter = null) { 
    try { 
        const response = await fetch(API_URL + 'save_feeding_record.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'save', plate_type: plateType, portion: portion, weight_final: weightAfter, user_id: <?php echo $userId; ?> }), credentials: 'include' }); 
        const data = await response.json(); 
        if (data.success) { 
            if (currentPanel === 'recentFeedings') loadRecentFeedingsContent(recentFilter); 
            if (currentPanel === 'history') loadFeedingHistory(currentFilter); 
        } 
    } catch (error) { 
        console.error('[saveFeedingRecord] Network error:', error); 
    } 
}

function sendDisposeCommand() { 
    const weight = parseFloat(document.getElementById('currentWeightPanel')?.textContent) || 0; 
    if (weight < 5.5) { 
        addSystemEvent(`⚠️ Dispose blocked — no plate detected (${weight.toFixed(1)}g)`, 'warning'); 
        return; 
    } 
    if (!confirm(`Dispose plate (${weight.toFixed(1)}g)?`)) return; 
    fetch(API_URL + 'send_dispose_command.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ command: 'dispose' }), credentials: 'include' }).then(r => r.json()).then(data => { 
        if (data.success) { 
            addSystemEvent(`🗑️ Plate disposed (${weight.toFixed(1)}g)`, 'info'); 
        } else { 
            addSystemEvent('❌ Dispose command failed!', 'error'); 
        } 
    }).catch(() => addSystemEvent('❌ Dispose failed — network error', 'error')); 
}

function openPlateModal() { 
    const portion = parseInt(document.getElementById('portionSlider')?.value || 30); 
    const foodWeight = parseFloat(document.getElementById('currentWeightPanel')?.textContent) || 0; 
    const hasPlate = (foodWeight >= 5.5) && !currentNoPlate && currentPlateInside; 
    if (hasPlate) { 
        showFeedAgainModal(portion, 0, foodWeight); 
        return; 
    } 
    const currentTime = Date.now(); 
    if (lastFeedingTime > 0 && (currentTime - lastFeedingTime) < FEED_COOLDOWN_MS) { 
        showFeedAgainModal(portion, Math.ceil((FEED_COOLDOWN_MS - (currentTime - lastFeedingTime)) / 1000), foodWeight); 
        return; 
    } 
    if (foodWeight < 5.5 || currentNoPlate) { 
        if (confirm(`🍽️ Auto: New Plate\n\nPortion: ${portion}g\n\nProceed?`)) { 
            sendFeedCommand('new', portion); 
        } 
        return; 
    } 
    selectedPlateType = null; 
    document.getElementById('plateModal').style.display = 'flex'; 
    document.getElementById('plateConfirmRow').style.display = 'none'; 
    document.getElementById('stockCard').classList.remove('selected'); 
    document.getElementById('newCard').classList.remove('selected'); 
}

function confirmFeed() { 
    if (!selectedPlateType) { 
        alert('Please select a plate type'); 
        return; 
    } 
    const portion = parseInt(document.getElementById('portionSlider').value); 
    closePlateModal(); 
    sendFeedCommand(selectedPlateType, portion); 
}

function resetNoPlateState() {
    currentNoPlate = false;
    _noPlateState = false;
    const platePosEl = document.getElementById('platePositionPanel');
    if (platePosEl) platePosEl.innerHTML = '<span style="color:var(--text-muted);">--</span>';
}

// MODIFIED sendFeedCommand with TEST MODE support
function sendFeedCommand(plateType, portion, isScheduled = false) { 
    const btn = document.getElementById('feedNowBtn'); 
    
    if (btn && !isScheduled) { 
        btn.disabled = true; 
        btn.innerHTML = '<span>⏳</span><span>Processing...</span>'; 
    } 
    
    // CHECK IF TEST MODE IS ENABLED OR ESP32 IS NOT CONNECTED
    if (testMode || !_connState) {
        const mode = testMode ? 'TEST MODE' : 'SIMULATED (No ESP)';
        console.log(`[${mode}] Feeding command - Plate:`, plateType, 'Portion:', portion, 'Scheduled:', isScheduled);
        
        const message = isScheduled ? 
            `⏰ [${mode}] Scheduled feeding - ${plateType === 'new' ? 'New Plate' : 'Stock Plate'} (${portion}g)` :
            `🍖 [${mode}] Manual feeding - ${plateType === 'new' ? 'New Plate' : 'Stock Plate'} (${portion}g)`;
        
        addSystemEvent(message, 'success');
        lastFeedingTime = Date.now();
        
        saveFeedingRecord(plateType, portion, null);
        
        if (btn && !isScheduled) { 
            btn.style.background = '#10B981'; 
            btn.innerHTML = '<span>✅</span><span>Simulated!</span>'; 
            setTimeout(() => { 
                btn.style.background = ''; 
                updateFeedButtonLabel(); 
                btn.disabled = false; 
            }, 2000); 
        }
        
        if (currentPanel === 'recentFeedings') loadRecentFeedingsContent(recentFilter); 
        if (currentPanel === 'history') loadFeedingHistory(currentFilter);
        
        if (currentPanel === 'liveStatus') {
            const lastFeedingEl = document.getElementById('lastFeedingPanel');
            if (lastFeedingEl) {
                lastFeedingEl.textContent = new Date().toLocaleTimeString();
            }
        }
        
        return;
    }
    
    // ORIGINAL CODE FOR WHEN ESP32 IS CONNECTED AND TEST MODE IS OFF
    resetNoPlateState(); 
    
    const body = { 
        command: 'feed_now', 
        plate_type: plateType, 
        portion: portion,
        scheduled: isScheduled
    };
    
    fetch(API_URL + 'send_feeding_command.php', { 
        method: 'POST', 
        headers: { 'Content-Type': 'application/json' }, 
        credentials: 'include', 
        body: JSON.stringify(body) 
    }).then(r => r.json()).then(data => { 
        if (data.success) { 
            const message = isScheduled ? 
                `⏰ Scheduled feeding - ${plateType === 'new' ? 'New Plate' : 'Stock Plate'} (${portion}g)` :
                `🍖 Feeding command sent — ${plateType === 'new' ? 'New Plate' : 'Stock Plate'} (${portion}g)`;
            addSystemEvent(message, 'success'); 
            lastFeedingTime = Date.now(); 
            
            if (btn && !isScheduled) { 
                btn.style.background = '#10B981'; 
                btn.innerHTML = '<span>✅</span><span>Sent!</span>'; 
                setTimeout(() => { 
                    btn.style.background = ''; 
                    updateFeedButtonLabel(); 
                    btn.disabled = false; 
                }, 2000); 
            } 
            
            if (currentPanel === 'recentFeedings') loadRecentFeedingsContent(recentFilter); 
            if (currentPanel === 'history') loadFeedingHistory(currentFilter); 
        } else { 
            addSystemEvent(`❌ Feeding failed: ${data.message || 'Unknown error'}`, 'error'); 
            if (btn && !isScheduled) { 
                btn.disabled = false; 
                updateFeedButtonLabel(); 
            } 
        } 
    }).catch(err => { 
        console.error('[sendFeedCommand] NETWORK ERROR:', err); 
        addSystemEvent('❌ Network error: Cannot send feed command', 'error'); 
        if (btn && !isScheduled) { 
            btn.disabled = false; 
            updateFeedButtonLabel(); 
        } 
    }); 
}

function startScheduleChecker() {
    if (scheduleCheckInterval) clearInterval(scheduleCheckInterval);
    scheduleCheckInterval = setInterval(() => {
        const now = new Date();
        const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
        
        for (let i = 0; i < slotData.length; i++) {
            const schedule = slotData[i];
            if (!schedule || schedule.is_active != 1) continue;
            
            const scheduleTime = schedule.schedule_time;
            if (scheduleTime === currentTime) {
                const lastTrigger = scheduleLastTrigger[i];
                if (!lastTrigger || (now - new Date(lastTrigger)) > 60000) {
                    scheduleLastTrigger[i] = now;
                    const mealNames = ['Breakfast', 'Lunch', 'Dinner'];
                    addSystemEvent(`⏰ SCHEDULE TRIGGERED: ${mealNames[i]} time! Adding ${schedule.portion}g to plate...`, 'success');
                    // FIXED: Use 'stock' with true flag para hindi mag-dispose
                    sendFeedCommand('stock', schedule.portion, true);
                    
                    if (currentPanel === 'schedule') {
                        renderScheduleList();
                    }
                }
            }
        }
    }, 1000);
}

function closePlateModal() { document.getElementById('plateModal').style.display = 'none'; selectedPlateType = null; }
function selectPlate(type) { selectedPlateType = type; document.getElementById('stockCard').classList.toggle('selected', type === 'stock'); document.getElementById('newCard').classList.toggle('selected', type === 'new'); const portion = document.getElementById('portionSlider').value; document.getElementById('confirmPortion').textContent = portion + 'g'; document.getElementById('selectedPlateLabel').textContent = type === 'new' ? '🍽️ New Plate' : '🫙 Stock Plate'; document.getElementById('plateConfirmRow').style.display = 'block'; }

function showFeedAgainModal(portion, timeLeft, currentWeight) { 
    currentFeedPortion = portion; 
    const modal = document.getElementById('feedAgainModal'); 
    const plateInfoDiv = document.getElementById('feedAgainPlateInfo'); 
    if (plateInfoDiv) { 
        const icon = timeLeft > 0 ? '⏰' : '🍽️'; 
        const title = timeLeft > 0 ? `Kakatapos lang mag-feed! (${timeLeft}s ago)` : 'May plate na sa feeder!'; 
        plateInfoDiv.innerHTML = `<div style="display:flex;align-items:center;justify-content:center;gap:10px;"><span style="font-size:1.2rem;">${icon}</span><span style="font-weight:500;">${title}</span></div><div style="margin-top:8px;">⚖️ Kasalukuyang timbang: <strong style="color:var(--primary-accent);">${currentWeight ? currentWeight.toFixed(1) : '0.0'}</strong> g</div><div style="margin-top:5px;font-size:0.8rem;color:var(--text-muted);">📊 Portion na idadagdag: <strong>${portion}</strong> g</div>`; 
        plateInfoDiv.style.background = timeLeft > 0 ? 'var(--warning-light)' : 'var(--badge-bg)'; 
        plateInfoDiv.style.border = timeLeft > 0 ? '1px solid #F59E0B' : '1px solid var(--card-border)'; 
    } 
    if (modal) modal.style.display = 'flex'; 
}

function closeFeedAgainModal() { const modal = document.getElementById('feedAgainModal'); if (modal) modal.style.display = 'none'; }

function addMoreFoodToExistingPlate() { 
    const portion = currentFeedPortion || parseInt(document.getElementById('portionSlider').value); 
    const currentWeight = parseFloat(document.getElementById('currentWeightPanel')?.textContent) || 0; 
    if (!confirm(`➕ Add More Food\n\nMagdagdag ng ${portion}g sa kasalukuyang plate (${currentWeight.toFixed(1)}g)?\n\nProceed?`)) return; 
    closeFeedAgainModal(); 
    const btn = document.getElementById('feedNowBtn'); 
    if (btn) { 
        btn.disabled = true; 
        btn.innerHTML = '<span class="btn-icon">⏳</span><span>Adding more food...</span>'; 
    } 
    addSystemEvent(`🔄 Adding ${portion}g to existing plate...`, 'info'); 
    fetch(API_URL + 'send_plate_in_command.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ command: 'plate_in' }), credentials: 'include' }).then(r => r.json()).then(data => { 
        if (data.success) { 
            addSystemEvent('🔄 Plate returned inside for adding more food', 'info'); 
            setTimeout(() => { 
                fetch(API_URL + 'send_feeding_command.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ command: 'feed_now', plate_type: 'stock', portion }) }).then(r => r.json()).then(data => { 
                    if (data.success) { 
                        addSystemEvent(`➕ Added ${portion}g to existing plate successfully`, 'success'); 
                        if (btn) { 
                            btn.style.background = '#10B981'; 
                            btn.innerHTML = '<span class="btn-icon">✅</span><span>Added!</span>'; 
                            setTimeout(() => { 
                                btn.style.background = ''; 
                                updateFeedButtonLabel(); 
                                btn.disabled = false; 
                            }, 2000); 
                        } 
                    } else { 
                        throw new Error('Feed failed'); 
                    } 
                }).catch(() => { 
                    addSystemEvent('❌ Failed to add food to plate', 'error'); 
                    if (btn) { 
                        btn.disabled = false; 
                        updateFeedButtonLabel(); 
                    } 
                }); 
            }, 1500); 
        } else { 
            throw new Error('Failed to return plate'); 
        } 
    }).catch(() => { 
        addSystemEvent('❌ Failed to return plate for feeding', 'error'); 
        if (btn) { 
            btn.disabled = false; 
            updateFeedButtonLabel(); 
        } 
    }); 
}

function disposeAndFeedNew() { 
    const portion = currentFeedPortion || parseInt(document.getElementById('portionSlider').value); 
    const currentWeight = parseFloat(document.getElementById('currentWeightPanel')?.textContent) || 0; 
    if (!confirm(`🗑️ Dispose & Feed New\n\nItatapon ang plate (${currentWeight.toFixed(1)}g) at maglalagay ng bagong ${portion}g.\n\nProceed?`)) return; 
    closeFeedAgainModal(); 
    const btn = document.getElementById('feedNowBtn'); 
    if (btn) { 
        btn.disabled = true; 
        btn.innerHTML = '<span class="btn-icon">⏳</span><span>Disposing & Feeding...</span>'; 
    } 
    addSystemEvent(`🗑️ Disposing plate (${currentWeight.toFixed(1)}g) and feeding new ${portion}g...`, 'warning'); 
    fetch(API_URL + 'send_dispose_command.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ command: 'dispose' }) }).then(r => r.json()).then(data => { 
        if (data.success) { 
            addSystemEvent('🗑️ Plate disposed successfully', 'info'); 
            return fetch(API_URL + 'send_feeding_command.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ command: 'feed_now', plate_type: 'new', portion }) }); 
        } else { 
            throw new Error('Dispose failed'); 
        } 
    }).then(r => r.json()).then(data => { 
        if (data.success) { 
            addSystemEvent(`🍖 New plate fed with ${portion}g successfully`, 'success'); 
            if (btn) { 
                btn.style.background = '#10B981'; 
                btn.innerHTML = '<span class="btn-icon">✅</span><span>Done!</span>'; 
                setTimeout(() => { 
                    btn.style.background = ''; 
                    updateFeedButtonLabel(); 
                    btn.disabled = false; 
                }, 2000); 
            } 
        } else { 
            throw new Error('Feed failed'); 
        } 
    }).catch(() => { 
        addSystemEvent('❌ Dispose and feed operation failed', 'error'); 
        if (btn) { 
            btn.disabled = false; 
            updateFeedButtonLabel(); 
        } 
    }); 
}

async function loadSchedules() { 
    try { 
        const r = await fetch(SCHED_API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'get' }), credentials: 'include' }); 
        const data = await r.json(); 
        slotData = [null, null, null]; 
        if (data.success && data.schedules) data.schedules.forEach(s => { 
            if (s.slot_index >= 0 && s.slot_index <= 2) slotData[s.slot_index] = s; 
        }); 
        if (currentPanel === 'schedule') { 
            renderScheduleList(); 
        } 
    } catch(e) { 
        console.error(e); 
    } 
}

function refreshSchedules() { loadSchedules(); }

async function deleteSchedule() { 
    if (!currentScheduleId) { 
        alert('No schedule to delete'); 
        return; 
    } 
    const slotIndex = editingSlotIndex !== null ? editingSlotIndex : activeSlotIndex;
    const slotNames = ['Breakfast', 'Lunch', 'Dinner']; 
    const slotName = slotNames[slotIndex]; 
    const existing = slotData[slotIndex]; 
    const scheduleTime = existing ? formatFullTime(existing.schedule_time) : ''; 
    if (!confirm(`Are you sure you want to delete ${slotName} schedule (${scheduleTime} - ${existing?.portion}g)?\n\nThis action cannot be undone.`)) return; 
    try { 
        const response = await fetch(SCHED_API, { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify({ action: 'delete', id: currentScheduleId }), 
            credentials: 'include' 
        }); 
        const data = await response.json(); 
        if (data.success) { 
            closeScheduleModal(); 
            await loadSchedules(); 
            addSystemEvent(`🗑️ Schedule deleted: ${slotName} at ${scheduleTime}`, 'warning'); 
            showToastNotification(`🗑️ ${slotName} schedule deleted`, 'warning'); 
            scheduleLastTrigger[slotIndex] = null; 
            if (currentPanel === 'schedule') {
                renderScheduleList();
            }
            isEditingSchedule = false;
            currentScheduleId = null;
            editingSlotIndex = null;
        } else { 
            alert('Error: ' + (data.message || 'Unknown')); 
            addSystemEvent(`❌ Failed to delete schedule: ${data.message || 'Unknown error'}`, 'error'); 
        } 
    } catch(e) { 
        console.error('[deleteSchedule] Error:', e); 
        alert('Network error'); 
        addSystemEvent('❌ Network error while deleting schedule', 'error'); 
    } 
}

function escapeHtml(s) { if (!s) return ''; return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ==================== EVENT LISTENERS & INITIALIZATION ====================
document.addEventListener('click', function(event) { 
    const burgerContainer = document.querySelector('.burger-menu-container'); 
    if (burgerContainer && !burgerContainer.contains(event.target) && dropdownOpen) { 
        document.getElementById('dropdownMenu').classList.remove('show'); 
        dropdownOpen = false; 
    } 
    const bellContainer = document.querySelector('.notification-bell-container'); 
    if (bellContainer && !bellContainer.contains(event.target) && notificationDropdownOpen) { 
        document.getElementById('notificationDropdown').classList.remove('show'); 
        notificationDropdownOpen = false; 
    } 
});

document.addEventListener('DOMContentLoaded', () => { 
    loadThemePreference(); 
    loadTestModePreference();  // Load test mode preference
    loadNotificationsFromStorage(); 
    loadWaterNotificationsFromStorage(); 
    
    const dotEl = document.getElementById('connectionDot'); 
    const textEl = document.getElementById('connectionText'); 
    if (dotEl) {
        dotEl.className = 'live-dot';
    }
    if (textEl) {
        textEl.textContent = 'Disconnected';
        textEl.style.color = '#EF4444';
    }
    _connState = false;
    
    fetchArduinoStatus(); 
    setInterval(fetchArduinoStatus, 5000); 
    loadSchedules(); 
    setInterval(loadSchedules, 60000); 
    startScheduleChecker(); 
    addSystemEvent('🚀 IntelliFeed dashboard initialized and ready', 'success', true); 
    renderLiveStatusPanel(); 
    currentPanel = 'liveStatus'; 
    document.querySelectorAll('.dropdown-item').forEach(item => item.classList.remove('active')); 
    const liveStatusItem = document.querySelector('.dropdown-item[data-panel="liveStatus"]'); 
    if (liveStatusItem) liveStatusItem.classList.add('active'); 
    const addMoreBtn = document.getElementById('addMoreFoodBtn'); 
    const disposeAndFeedBtn = document.getElementById('disposeAndFeedBtn'); 
    if (addMoreBtn) addMoreBtn.addEventListener('click', addMoreFoodToExistingPlate); 
    if (disposeAndFeedBtn) disposeAndFeedBtn.addEventListener('click', disposeAndFeedNew); 
});
</script>
</body>
</html>
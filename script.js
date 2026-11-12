// ---------- STATE ----------
let totalFoodDispensed = 90;
let feedingCount = 3;
let currentWaterLevel = 180;
const MAX_WATER_LEVEL = 270;
let journalNotes = [];

// Schedule state
let feedingSchedules = [];
let nextScheduledFeeding = null;

// DOM elements
const mealsSpan = document.getElementById('mealsCount');
const nextMealSpan = document.getElementById('nextMeal');
const nextFeedingDisplay = document.getElementById('nextFeedingDisplay');
const waterPercentSpan = document.getElementById('waterPercentage');
const waterBadge = document.getElementById('waterBadge');
const currentWaterSpan = document.getElementById('currentWater');
const waterFill = document.getElementById('waterFill');
const lastFeedingSpan = document.getElementById('lastFeeding');
const totalFoodSpan = document.getElementById('totalFood');
const totalFoodText = document.getElementById('totalFoodText');
const feedingCountSpan = document.getElementById('feedingCount');
const scheduledTodaySpan = document.getElementById('scheduledToday');
const progressRing = document.getElementById('progressRing');
const portionSlider = document.getElementById('portionSlider');
const portionValue = document.getElementById('portionValue');
const currentPortionSpan = document.getElementById('currentPortion');
const logEntries = document.getElementById('logEntries');
const scheduleList = document.getElementById('scheduleList');
const scheduleCount = document.getElementById('scheduleCount');

// Modal elements
const scheduleModal = document.getElementById('scheduleModal');

// ---------- CAMERA ACTION CHECK ----------
function checkForCameraActions() {
    const action = localStorage.getItem('intellifeed_action');
    if (action) {
        try {
            const data = JSON.parse(action);

            if (data.action === 'feed') {
                // Trigger feed
                setTimeout(() => {
                    feedNow(); // Your existing feed function
                    showNotification('🍖 Manual feeding triggered from camera!', 'success');
                }, 500);
            } else if (data.action === 'checkWater') {
                // Check water
                showNotification('💧 Water level check requested from camera', 'info');
                // Optionally refresh water display
                updateWaterDisplay();
            }

            // Clear the action
            localStorage.removeItem('intellifeed_action');
        } catch (e) {
            console.error('Error parsing action:', e);
        }
    }
}

// ---------- MANUAL TOGGLE FUNCTION ----------
window.toggleManual = function () {
    const content = document.getElementById('manualContent');
    const toggle = document.getElementById('manualToggle');
    content.classList.toggle('show');
    toggle.textContent = content.classList.contains('show') ? '📖' : '📘';
};

// ---------- BACK TO TOP FUNCTION ----------
window.scrollToTop = function () {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
};

// Show/hide back to top button based on scroll position
window.onscroll = function () {
    const backToTopBtn = document.getElementById('backToTopBtn');
    if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
        backToTopBtn.style.opacity = '1';
        backToTopBtn.style.visibility = 'visible';
    } else {
        backToTopBtn.style.opacity = '0.7';
        backToTopBtn.style.visibility = 'visible';
    }
};

// ---------- SCHEDULE MODAL FUNCTIONS ----------
window.openScheduleModal = function () {
    scheduleModal.style.display = 'flex';
    scheduleModal.classList.add('show');

    // Set default time to current time + 1 hour
    const now = new Date();
    now.setHours(now.getHours() + 1);
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('scheduleTime').value = `${hours}:${minutes}`;
};

window.closeScheduleModal = function () {
    scheduleModal.style.display = 'none';
    scheduleModal.classList.remove('show');
};

// Close modal when clicking outside
window.onclick = function (event) {
    if (event.target === scheduleModal) {
        closeScheduleModal();
    }
};

// ---------- SCHEDULE MANAGEMENT ----------
function loadSchedules() {
    const saved = localStorage.getItem('intellifeed_schedules');
    if (saved) {
        try {
            feedingSchedules = JSON.parse(saved);
            // Convert string times back to comparable format
            feedingSchedules = feedingSchedules.map(s => ({
                ...s,
                id: s.id || Date.now() + Math.random()
            }));
        } catch (e) {
            feedingSchedules = [];
        }
    } else {
        // Default schedules
        feedingSchedules = [
            { id: 1, time: '08:00', meal: 'Breakfast', portion: 30, repeat: 'daily', active: true },
            { id: 2, time: '13:00', meal: 'Lunch', portion: 30, repeat: 'daily', active: true },
            { id: 3, time: '18:00', meal: 'Dinner', portion: 30, repeat: 'daily', active: true }
        ];
    }
    renderSchedules();
    checkScheduledFeedings();
}

function saveSchedules() {
    localStorage.setItem('intellifeed_schedules', JSON.stringify(feedingSchedules));
    renderSchedules();
    checkScheduledFeedings();
}

window.addSchedule = function () {
    const timeInput = document.getElementById('scheduleTime');
    const mealSelect = document.getElementById('scheduleMeal');
    const portionInput = document.getElementById('schedulePortion');
    const repeatSelect = document.getElementById('scheduleRepeat');

    const time = timeInput.value;
    const meal = mealSelect.value;
    const portion = parseInt(portionInput.value);
    const repeat = repeatSelect.value;

    if (!time || !meal || !portion) {
        showNotification('Please fill all fields', 'warning');
        return;
    }

    const newSchedule = {
        id: Date.now() + Math.random(),
        time: time,
        meal: meal,
        portion: portion,
        repeat: repeat,
        active: true,
        created: new Date().toISOString()
    };

    feedingSchedules.push(newSchedule);
    saveSchedules();
    closeScheduleModal();

    // Update schedule count
    updateScheduleCount();

    showNotification(`✅ Schedule added: ${formatTime(time)} - ${meal}`, 'success');
    addToLog(`📅 New schedule: ${formatTime(time)} ${meal} (${portion}g)`, 'schedule');
};

window.editSchedule = function (id) {
    const schedule = feedingSchedules.find(s => s.id === id);
    if (!schedule) return;

    // Populate modal with schedule data
    document.getElementById('scheduleTime').value = schedule.time;
    document.getElementById('scheduleMeal').value = schedule.meal;
    document.getElementById('schedulePortion').value = schedule.portion;
    document.getElementById('scheduleRepeat').value = schedule.repeat;

    // Remove old schedule and open modal
    feedingSchedules = feedingSchedules.filter(s => s.id !== id);
    openScheduleModal();
};

window.deleteSchedule = function (id) {
    if (confirm('Are you sure you want to delete this schedule?')) {
        feedingSchedules = feedingSchedules.filter(s => s.id !== id);
        saveSchedules();
        showNotification('🗑️ Schedule deleted', 'info');
        addToLog('🗑️ Schedule deleted', 'schedule');
    }
};

window.toggleScheduleActive = function (id) {
    const schedule = feedingSchedules.find(s => s.id === id);
    if (schedule) {
        schedule.active = !schedule.active;
        saveSchedules();
        showNotification(schedule.active ? '✅ Schedule enabled' : '⏸️ Schedule paused', 'info');
    }
};

window.clearAllSchedules = function () {
    if (confirm('Are you sure you want to delete ALL schedules?')) {
        feedingSchedules = [];
        saveSchedules();
        showNotification('🗑️ All schedules cleared', 'info');
        addToLog('🗑️ All schedules cleared', 'schedule');
    }
};

function renderSchedules() {
    if (!scheduleList) return;

    if (feedingSchedules.length === 0) {
        scheduleList.innerHTML = '<div class="empty-schedule"><p>📭 No schedules set</p><span class="suggestion">Click + Add to create a feeding schedule</span></div>';
        if (scheduleCount) scheduleCount.textContent = '0 schedules';
        return;
    }

    // Sort schedules by time
    const sorted = [...feedingSchedules].sort((a, b) => a.time.localeCompare(b.time));

    let html = '';
    const now = new Date();
    const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

    sorted.forEach(schedule => {
        const isNext = nextScheduledFeeding && nextScheduledFeeding.id === schedule.id;
        const timeFormatted = formatTime(schedule.time);
        const timeIcon = getTimeIcon(schedule.time);

        html += `
            <li class="schedule-item ${isNext ? 'next-meal' : ''} ${!schedule.active ? 'inactive' : ''}">
                <div class="schedule-info">
                    <span class="time">${timeIcon} ${timeFormatted}</span>
                    <span class="meal">${schedule.meal}</span>
                    <span class="portion">${schedule.portion}g</span>
                    ${schedule.repeat !== 'daily' ? `<span class="repeat-badge">${schedule.repeat}</span>` : ''}
                </div>
                <div class="schedule-actions">
                    <button class="btn-edit-schedule" onclick="editSchedule(${schedule.id})" title="Edit">✏️</button>
                    <button class="btn-delete-schedule" onclick="deleteSchedule(${schedule.id})" title="Delete">🗑️</button>
                </div>
            </li>
        `;
    });

    scheduleList.innerHTML = html;
    updateScheduleCount();
}

function formatTime(time24) {
    const [hours, minutes] = time24.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

function getTimeIcon(time) {
    const hour = parseInt(time.split(':')[0]);
    if (hour < 12) return '🌅';
    if (hour < 17) return '☀️';
    return '🌙';
}

function updateScheduleCount() {
    if (scheduleCount) {
        const activeCount = feedingSchedules.filter(s => s.active).length;
        scheduleCount.textContent = `${activeCount} active schedule${activeCount !== 1 ? 's' : ''}`;
    }

    // Update scheduled today count
    if (scheduledTodaySpan) {
        scheduledTodaySpan.textContent = feedingSchedules.filter(s => s.active).length;
    }
}

// Check for scheduled feedings every minute
function checkScheduledFeedings() {
    const now = new Date();
    const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

    // Find next scheduled feeding
    const activeSchedules = feedingSchedules.filter(s => s.active);

    if (activeSchedules.length === 0) {
        nextScheduledFeeding = null;
        if (nextFeedingDisplay) nextFeedingDisplay.textContent = 'No schedule';
        if (nextMealSpan) nextMealSpan.textContent = 'No schedule';
        return;
    }

    // Sort schedules by time
    const sorted = [...activeSchedules].sort((a, b) => a.time.localeCompare(b.time));

    // Find next schedule (today)
    let nextSchedule = sorted.find(s => s.time > currentTime);

    // If no schedule left today, take first schedule of tomorrow
    if (!nextSchedule) {
        nextSchedule = sorted[0];
    }

    nextScheduledFeeding = nextSchedule;

    if (nextFeedingDisplay) {
        nextFeedingDisplay.textContent = formatTime(nextSchedule.time);
    }
    if (nextMealSpan) {
        nextMealSpan.textContent = formatTime(nextSchedule.time);
    }

    // Check if it's time to feed (within 1 minute)
    if (nextSchedule.time === currentTime) {
        // Check if we haven't already fed this schedule today
        const lastFedKey = `last_fed_${nextSchedule.id}_${now.toDateString()}`;
        const alreadyFed = localStorage.getItem(lastFedKey);

        if (!alreadyFed) {
            executeScheduledFeed(nextSchedule);
            localStorage.setItem(lastFedKey, 'true');

            // Clear tomorrow's flag
            setTimeout(() => {
                const tomorrow = new Date(now);
                tomorrow.setDate(tomorrow.getDate() + 1);
                const tomorrowKey = `last_fed_${nextSchedule.id}_${tomorrow.toDateString()}`;
                localStorage.removeItem(tomorrowKey);
            }, 60000); // Remove after 1 minute
        }
    }
}

function executeScheduledFeed(schedule) {
    // Simulate feeding
    totalFoodDispensed += schedule.portion;
    feedingCount++;

    totalFoodSpan.textContent = totalFoodDispensed;
    totalFoodText.textContent = totalFoodDispensed + ' grams';
    feedingCountSpan.textContent = feedingCount;
    mealsSpan.textContent = feedingCount;

    lastFeedingSpan.textContent = 'Just now';
    lastFeedingSpan.style.color = 'var(--success)';

    updateProgressRing();
    consumeWater(schedule.portion);

    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });

    addToLog(`⏰ Scheduled: ${schedule.meal} (${schedule.portion}g)`, 'schedule');
    showNotification(`⏰ Scheduled feeding: ${schedule.meal} (${schedule.portion}g)`, 'info');

    // Show special notification for scheduled feeding
    const notification = document.createElement('div');
    notification.className = 'notification special';
    notification.innerHTML = `🍽️ Scheduled feeding started: ${schedule.meal}`;
    notification.style.cssText = `
        position: fixed; top: 80px; right: 20px;
        background: var(--gradient-1);
        color: white; padding: 15px 25px; border-radius: 15px;
        box-shadow: var(--shadow-lg); z-index: 1000;
        animation: slideInRight 0.3s ease;
        font-weight: 600;
    `;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 5000);
}

// Check schedules every minute
setInterval(checkScheduledFeedings, 60000);

// ---------- INIT ----------
document.addEventListener('DOMContentLoaded', function () {
    // Check for camera actions on load
    checkForCameraActions();

    // Set up interval to check for camera actions
    setInterval(checkForCameraActions, 1000);

    // slider listener
    portionSlider.addEventListener('input', function (e) {
        const val = e.target.value;
        portionValue.textContent = val + ' grams';
        currentPortionSpan.textContent = val + 'g';
    });

    // restore journal from localStorage
    loadJournal();

    // load schedules
    loadSchedules();

    // update displays
    updateWaterDisplay();
    updateProgressRing();
    updateStats();

    // render feeding tips
    renderTips();

    // welcome
    setTimeout(() => showNotification('✨ Welcome to IntelliFeed!', 'info'), 500);

    // Initial schedule check
    checkScheduledFeedings();
});

// ---------- WATER ----------
window.refillWater = function () {
    currentWaterLevel = MAX_WATER_LEVEL;
    updateWaterDisplay();
    addToLog('💧 Water tank refilled', 'refill');
    showNotification('💧 Water tank refilled to maximum!', 'success');
};

function consumeWater(amount) {
    const waterConsumed = Math.floor(amount * 0.3);
    currentWaterLevel = Math.max(0, currentWaterLevel - waterConsumed);
    updateWaterDisplay();
    if (currentWaterLevel <= 50) showNotification('⚠️ Critical: Water level very low!', 'error');
    else if (currentWaterLevel <= 100) showNotification('⚠️ Warning: Water level is low', 'warning');
}

function updateWaterDisplay() {
    const percentage = (currentWaterLevel / MAX_WATER_LEVEL) * 100;
    if (waterFill) waterFill.style.height = percentage + '%';
    if (currentWaterSpan) currentWaterSpan.textContent = currentWaterLevel;
    if (waterPercentSpan) waterPercentSpan.textContent = Math.round(percentage) + '%';
    if (waterBadge) {
        if (currentWaterLevel <= 50) {
            waterBadge.textContent = 'Critical';
            waterBadge.style.background = 'var(--danger)';
        } else if (currentWaterLevel <= 100) {
            waterBadge.textContent = 'Low';
            waterBadge.style.background = 'var(--warning)';
        } else {
            waterBadge.textContent = 'Good';
            waterBadge.style.background = 'var(--success)';
        }
    }
}

// ---------- FEEDING ----------
window.feedNow = function () {
    const portion = parseInt(portionSlider.value);
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });

    lastFeedingSpan.textContent = 'Just now';
    lastFeedingSpan.style.color = 'var(--success)';

    totalFoodDispensed += portion;
    feedingCount++;

    totalFoodSpan.textContent = totalFoodDispensed;
    totalFoodText.textContent = totalFoodDispensed + ' grams';
    feedingCountSpan.textContent = feedingCount;
    mealsSpan.textContent = feedingCount;

    updateProgressRing();
    consumeWater(portion);
    addToLog(`🍖 Manual feed at ${timeString} (${portion}g)`, 'feed');

    if (feedingCount % 3 === 0) showNotification('🎉 Your pet says thank you for ' + feedingCount + ' meals!', 'success');

    const feedBtn = document.querySelector('.btn-feed');
    feedBtn.style.transform = 'scale(0.95)';
    setTimeout(() => feedBtn.style.transform = '', 200);
};

window.resetCounter = function () {
    feedingCount = 0;
    totalFoodDispensed = 0;
    totalFoodSpan.textContent = '0';
    totalFoodText.textContent = '0 grams';
    feedingCountSpan.textContent = '0';
    mealsSpan.textContent = '0';
    lastFeedingSpan.textContent = 'Never';
    lastFeedingSpan.style.color = '';
    updateProgressRing();
    addToLog('🔄 Daily counter reset', 'reset');
    showNotification('🔄 Daily counter has been reset', 'info');
};

function addToLog(message, type = 'default') {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    const entry = document.createElement('div');
    entry.className = 'log-entry';

    let icon = '📌';
    if (type === 'feed') icon = '🍖';
    else if (type === 'refill') icon = '💧';
    else if (type === 'reset') icon = '🔄';
    else if (type === 'schedule') icon = '⏰';

    entry.innerHTML = `<span class="log-time">${timeString}</span><span class="log-message">${icon} ${message}</span>`;
    logEntries.insertBefore(entry, logEntries.firstChild);

    // Keep only last 10 entries
    while (logEntries.children.length > 10) {
        logEntries.removeChild(logEntries.lastChild);
    }

    entry.style.animation = 'slideIn 0.3s ease';
}

function updateProgressRing() {
    if (!progressRing) return;
    const maxDaily = 300;
    const percentage = Math.min(totalFoodDispensed / maxDaily, 1);
    const circumference = 2 * Math.PI * 45;
    const offset = circumference * (1 - percentage);
    progressRing.style.strokeDasharray = circumference;
    progressRing.style.strokeDashoffset = offset;
    if (percentage >= 0.8) progressRing.style.stroke = 'var(--success)';
    else if (percentage >= 0.5) progressRing.style.stroke = 'var(--warning)';
    else progressRing.style.stroke = 'var(--primary)';
}

function updateStats() {
    mealsSpan.textContent = feedingCount;
}

// ---------- JOURNAL ----------
function loadJournal() {
    const saved = localStorage.getItem('intellifeed_journal');
    if (saved) {
        try { journalNotes = JSON.parse(saved); }
        catch (e) { journalNotes = []; }
    } else {
        journalNotes = [{ text: '🐾 Fluffy seems extra playful today!', timestamp: new Date(Date.now() - 86400000).toISOString() }];
    }
    renderJournal();
}

window.saveJournalNote = function () {
    const input = document.getElementById('journalInput');
    const text = input.value.trim();
    if (text === '') {
        showNotification('✏️ Write something before saving', 'warning');
        return;
    }
    const note = { text: text, timestamp: new Date().toISOString() };
    journalNotes.unshift(note);
    if (journalNotes.length > 10) journalNotes.pop();
    localStorage.setItem('intellifeed_journal', JSON.stringify(journalNotes));
    input.value = '';
    renderJournal();
    addToLog('📔 Journal entry saved', 'journal');
    showNotification('📔 Note saved to journal', 'success');
};

window.deleteJournalNote = function (index) {
    if (confirm('Delete this note?')) {
        journalNotes.splice(index, 1);
        localStorage.setItem('intellifeed_journal', JSON.stringify(journalNotes));
        renderJournal();
        showNotification('🗑️ Note deleted', 'info');
    }
};

function renderJournal() {
    const container = document.getElementById('journalEntries');
    if (!container) return;
    if (journalNotes.length === 0) {
        container.innerHTML = '<div class="journal-note" style="opacity:0.6">📭 No notes yet.</div>';
        return;
    }
    let html = '';
    journalNotes.forEach((note, idx) => {
        const date = new Date(note.timestamp);
        const timeStr = date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        html += `<div class="journal-note">
            <span class="note-time">📅 ${timeStr}</span>
            <span class="note-text">${escapeHtml(note.text)}</span>
            <button class="journal-delete" onclick="deleteJournalNote(${idx})" title="delete">🗑️</button>
        </div>`;
    });
    container.innerHTML = html;
}

function escapeHtml(unsafe) {
    return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

// ---------- NOTIFICATION ----------
function showNotification(message, type = 'info') {
    const notif = document.createElement('div');
    notif.className = `notification ${type}`;
    notif.innerHTML = message;
    notif.style.cssText = `
        position: fixed; top: 20px; right: 20px;
        background: ${type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--danger)' : type === 'warning' ? 'var(--warning)' : 'var(--primary)'};
        color: white; padding: 12px 24px; border-radius: 12px;
        box-shadow: var(--shadow-lg); z-index: 1000;
        animation: slideInRight 0.3s ease;
    `;
    document.body.appendChild(notif);
    setTimeout(() => {
        notif.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => { if (document.body.contains(notif)) document.body.removeChild(notif); }, 300);
    }, 3000);
}
window.showNotification = showNotification;

// Ctrl+Enter to save journal
document.addEventListener('keydown', function (e) {
    if (e.ctrlKey && e.key === 'Enter') {
        const active = document.activeElement;
        if (active && active.id === 'journalInput') {
            e.preventDefault();
            saveJournalNote();
        }
    }
});

// ---------- FEEDING TIPS RENDER ----------
function renderTips() {
    const tipsContainer = document.getElementById('tipsContainer');
    if (!tipsContainer) return;
    tipsContainer.innerHTML = `
        <div class="tip-category">
            <h3>🐶 Dogs (grams/day)</h3>
            <ul class="tip-list">
                <li><span>2 kg</span> <span class="grams">55–65 g</span></li>
                <li><span>5 kg</span> <span class="grams">90–110 g</span></li>
                <li><span>8 kg</span> <span class="grams">130–150 g</span></li>
                <li><span>10 kg</span> <span class="grams">160–180 g</span></li>
                <li><span>12 kg</span> <span class="grams">190–220 g</span></li>
                <li><span>15 kg</span> <span class="grams">220–250 g</span></li>
                <li><span>20 kg</span> <span class="grams">280–320 g</span></li>
                <li><span>25 kg</span> <span class="grams">330–370 g</span></li>
                <li><span>30 kg</span> <span class="grams">380–450 g</span></li>
                <li><span>35 kg</span> <span class="grams">450–520 g</span></li>
                <li><span>40 kg</span> <span class="grams">500–580 g</span></li>
                <li><span>45 kg</span> <span class="grams">580–650 g</span></li>
            </ul>
            <p style="font-size:0.8rem; margin-top:5px;">🍽️ Divide into 2 meals</p>
        </div>
        <div class="tip-category">
            <h3>🐱 Cats (grams/day)</h3>
            <ul class="tip-list">
                <li><span>3 kg</span> <span class="grams">40–50 g</span></li>
                <li><span>4 kg</span> <span class="grams">55–65 g</span></li>
                <li><span>5 kg</span> <span class="grams">65–75 g</span></li>
                <li><span>6 kg</span> <span class="grams">75–85 g</span></li>
                <li><span>7 kg</span> <span class="grams">85–95 g</span></li>
                <li><span>8 kg</span> <span class="grams">95–110 g</span></li>
                <li><span>9 kg</span> <span class="grams">110–125 g</span></li>
            </ul>
            <p style="font-size:0.8rem; margin-top:5px;">🍽️ 2–3 meals/day</p>
        </div>
        <div class="tip-category">
            <h3>⚖️ Why grams matter</h3>
            <p style="font-size:0.95rem;">Precision prevents obesity, ensures consistent nutrition, and supports healthy digestion. Our smart feeder dispenses by weight, not volume.</p>
        </div>
    `;
}
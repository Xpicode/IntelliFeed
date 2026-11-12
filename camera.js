// camera.js - Complete Pet Detection with Database Storage

// ========== CONFIGURATION ==========
const CONFIG = {
    CONFIDENCE_THRESHOLD: 0.6,
    DETECTION_INTERVAL: 200,
    PET_TIMEOUT: 3000,
    MAX_PETS: 10,
    RECOGNITION_THRESHOLD: 0.7,
};

// API URL for database
const API_URL = 'http://localhost/IntelliFeed/api/saved_pets.php';

// ========== DOM ELEMENTS ==========
const elements = {
    startBtn: document.getElementById('startCamera'),
    stopBtn: document.getElementById('stopCamera'),
    snapshotBtn: document.getElementById('snapshotBtn'),
    savePetBtn: document.getElementById('savePetBtn'),
    cameraFeed: document.getElementById('cameraFeed'),
    detectionStats: document.getElementById('detectionStats'),
    catCount: document.getElementById('catCount'),
    dogCount: document.getElementById('dogCount'),
    fpsCount: document.getElementById('fpsCount'),
    nowCount: document.getElementById('nowCount'),
    detectionLog: document.getElementById('detectionLog'),
    clearLogBtn: document.getElementById('clearLogBtn'),
    colorInfo: document.getElementById('colorInfo'),
    savedPetsList: document.getElementById('savedPetsList'),
    savedPetCount: document.getElementById('savedPetCount'),
    petSelectionModal: document.getElementById('petSelectionModal'),
    petGrid: document.getElementById('petGrid'),
    closeModalBtn: document.getElementById('closeModalBtn')
};

// Store saved pets globally for recognition
let savedPetsCache = [];

// ========== DATABASE FUNCTIONS ==========

// Load saved pets from database
async function loadSavedPets() {
    console.log('🔄 Loading saved pets...');
    try {
        const response = await fetch(`${API_URL}?action=get`, {
            credentials: 'include'
        });

        const responseText = await response.text();
        console.log('📄 Raw response from server:', responseText);

        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('❌ Failed to parse JSON:', e);
            addToLog('❌ Server returned invalid data', 'error');
            return;
        }

        if (data.success) {
            console.log(`✅ Loaded ${data.pets.length} saved pets`);
            savedPetsCache = data.pets;
            renderSavedPets(data.pets);
            updateSavedPetCount(data.pets.length);

            if (window.app && window.app.tracker) {
                window.app.tracker.updateRecognizedPets(savedPetsCache);
            }
        } else {
            console.error('Failed to load pets:', data.message);
            if (data.message === 'Not logged in') {
                window.location.href = 'login.php';
            }
        }
    } catch (error) {
        console.error('Error loading pets:', error);
        addToLog('❌ Could not load saved pets', 'error');
    }
}

// Save pet to database
async function savePetToDatabase(petId, name, type, color) {
    console.log('🔍 ===== SAVING PET =====');
    console.log('Data to save:', { petId, name, type, color });
    addToLog(`📤 Saving "${name}"...`, 'info');

    try {
        const requestBody = {
            action: 'save',
            petId: petId,
            name: name,
            type: type,
            color: color
        };

        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestBody),
            credentials: 'include'
        });

        const responseText = await response.text();

        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('❌ Failed to parse JSON:', e);
            addToLog(`❌ Server returned invalid response`, 'error');
            return false;
        }

        if (data.success) {
            addToLog(`✅ Saved "${name}" to your collection!`, 'success');
            setTimeout(() => {
                loadSavedPets();
            }, 500);
            return true;
        } else {
            console.error('❌ Server error:', data.message);
            addToLog(`❌ ${data.message}`, 'error');

            if (data.message === 'Not logged in') {
                addToLog('🔒 Session expired. Please login again.', 'error');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            }
            return false;
        }

    } catch (error) {
        console.error('❌ Network error:', error);
        addToLog(`❌ Connection error: ${error.message}`, 'error');
        return false;
    }
}

// Delete pet from database
async function deletePetFromDatabase(petId) {
    if (!confirm('Delete this pet from your collection?')) return;

    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete',
                petId: petId
            }),
            credentials: 'include'
        });

        const data = await response.json();

        if (data.success) {
            addToLog('🗑️ Pet deleted', 'info');
            savedPetsCache = savedPetsCache.filter(pet => (pet.pet_id || pet.id) !== petId);
            setTimeout(() => {
                loadSavedPets();
            }, 500);
        } else {
            addToLog(`❌ Failed to delete: ${data.message}`, 'error');
        }

    } catch (error) {
        console.error('Error deleting pet:', error);
        addToLog('❌ Error deleting pet', 'error');
    }
}

// Render saved pets in the UI
function renderSavedPets(pets) {
    const container = elements.savedPetsList;
    if (!container) return;

    if (!pets || pets.length === 0) {
        container.innerHTML = '<div class="no-pets">No saved pets yet. Click on a pet and save it!</div>';
        if (elements.savedPetCount) elements.savedPetCount.textContent = '0';
        return;
    }

    let html = '';
    pets.forEach(pet => {
        const emoji = pet.type === 'cat' ? '🐱' : '🐶';
        const lastSeen = new Date(pet.last_seen).toLocaleDateString();
        const firstSeen = new Date(pet.first_seen).toLocaleDateString();

        const petIdentifier = pet.pet_id || pet.id;

        html += `
            <div class="saved-pet-item" data-pet-id="${petIdentifier}">
                <div class="pet-icon">${emoji}</div>
                <div class="pet-info">
                    <div class="pet-name">${pet.name}</div>
                    <div class="pet-details">${pet.color || 'Unknown'} ${pet.type}</div>
                    <div class="pet-stats">
                        <span title="First seen">📅 ${firstSeen}</span> · 
                        <span title="Last seen">🕐 ${lastSeen}</span> · 
                        <span title="Times seen">👀 ${pet.times_seen}x</span>
                    </div>
                </div>
                <div class="pet-actions">
                    <button class="pet-action-btn delete" onclick="deletePetFromDatabase('${petIdentifier}')" title="Delete">🗑️</button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;

    if (elements.savedPetCount) {
        elements.savedPetCount.textContent = pets.length;
    }
}

// Update saved pet count
function updateSavedPetCount(count) {
    const countSpan = elements.savedPetCount;
    if (countSpan) {
        countSpan.textContent = count;
        countSpan.style.animation = 'none';
        countSpan.offsetHeight;
        countSpan.style.animation = 'pulse 0.3s ease';
    }
}

// Make functions global
window.deletePetFromDatabase = deletePetFromDatabase;
window.loadSavedPets = loadSavedPets;

// ========== COLOR DETECTION ==========
function getPetColor(r, g, b) {
    const brightness = (r + g + b) / 3;

    if (r > 200 && g > 100 && g < 200 && b < 100) return 'Orange';
    if (r > 180 && g > 80 && g < 160 && b < 80) return 'Ginger';
    if (r > 100 && r < 200 && g > 50 && g < 150 && b < 100) return 'Brown';
    if (r > 80 && r < 150 && g > 40 && g < 100 && b < 60) return 'Dark Brown';
    if (r < 70 && g < 70 && b < 70) return 'Black';
    if (r > 200 && g > 200 && b > 200) return 'White';
    if (r > 220 && g > 200 && b > 180) return 'Cream';
    if (Math.abs(r - g) < 30 && Math.abs(g - b) < 30) {
        if (brightness > 150) return 'Light Gray';
        if (brightness > 80) return 'Gray';
        return 'Dark Gray';
    }
    if (r > 180 && g > 140 && g < 200 && b < 120) return 'Golden';

    return 'Unknown';
}

// ========== PET ANALYZER ==========
class PetAnalyzer {
    analyzeColor(imageData, x, y, w, h) {
        const centerX = Math.floor(x + w / 2);
        const centerY = Math.floor(y + h / 2);

        if (centerX < 0 || centerX >= imageData.width || centerY < 0 || centerY >= imageData.height) {
            return 'Unknown';
        }

        const index = (centerY * imageData.width + centerX) * 4;
        const r = imageData.data[index];
        const g = imageData.data[index + 1];
        const b = imageData.data[index + 2];

        return getPetColor(r, g, b);
    }
}

// ========== PET TRACKER ==========
class PetTracker {
    constructor() {
        this.activePets = new Map();
        this.analyzer = new PetAnalyzer();
        this.catCount = 0;
        this.dogCount = 0;
        this.recognitionAttempts = new Map();
        this.recognizedPetsMap = new Map();
        this.petSnapshots = new Map(); // Store snapshots of each pet
    }

    generatePetId(pet) {
        const [x, y, w, h] = pet.bbox;
        const centerX = Math.round((x + w / 2) / 40);
        const centerY = Math.round((y + h / 2) / 40);
        return `${pet.class}_${centerX}_${centerY}`;
    }

    generatePetSignature(type, color) {
        return `${type}_${color}`;
    }

    takePetSnapshot(petId, imageData, bbox) {
        const [x, y, w, h] = bbox;

        // Create a canvas for the snapshot
        const snapshotCanvas = document.createElement('canvas');
        snapshotCanvas.width = w;
        snapshotCanvas.height = h;
        const ctx = snapshotCanvas.getContext('2d');

        // Extract the pet region from the image data
        const imageDataCanvas = document.createElement('canvas');
        imageDataCanvas.width = imageData.width;
        imageDataCanvas.height = imageData.height;
        const imageDataCtx = imageDataCanvas.getContext('2d');
        imageDataCtx.putImageData(imageData, 0, 0);

        ctx.drawImage(imageDataCanvas, x, y, w, h, 0, 0, w, h);

        this.petSnapshots.set(petId, snapshotCanvas.toDataURL());
    }

    getPetSnapshot(petId) {
        return this.petSnapshots.get(petId);
    }

    updateRecognizedPets(savedPets) {
        console.log('🔄 Updating recognized pets with saved pets:', savedPets);

        this.recognizedPetsMap.clear();
        savedPets.forEach(pet => {
            const signature = this.generatePetSignature(pet.type, pet.color);
            this.recognizedPetsMap.set(signature, pet);
        });

        let recognizedCount = 0;
        for (const [id, pet] of this.activePets) {
            const signature = this.generatePetSignature(pet.type, pet.color);
            const matchedPet = this.recognizedPetsMap.get(signature);

            if (matchedPet) {
                pet.recognizedName = matchedPet.name;
                pet.recognizedId = matchedPet.pet_id || matchedPet.id;
                recognizedCount++;
            }
        }

        console.log(`🎯 Recognized ${recognizedCount} out of ${this.activePets.size} active pets`);
        this.updateUI();
    }

    update(pets, imageData) {
        const now = Date.now();
        const currentPetIds = new Set();

        for (const pet of pets) {
            const petId = this.generatePetId(pet);
            currentPetIds.add(petId);

            if (!this.activePets.has(petId)) {
                const color = imageData ? this.analyzer.analyzeColor(imageData, ...pet.bbox) : 'Unknown';
                const signature = this.generatePetSignature(pet.class, color);

                const newPet = {
                    id: petId,
                    type: pet.class,
                    color: color,
                    signature: signature,
                    recognizedName: null,
                    recognizedId: null,
                    firstSeen: now,
                    lastSeen: now,
                    bbox: pet.bbox,
                    score: pet.score
                };

                // Take a snapshot of the pet
                if (imageData) {
                    this.takePetSnapshot(petId, imageData, pet.bbox);
                }

                const matchedPet = this.recognizedPetsMap.get(signature);
                if (matchedPet) {
                    newPet.recognizedName = matchedPet.name;
                    newPet.recognizedId = matchedPet.pet_id || matchedPet.id;
                }

                this.activePets.set(petId, newPet);

                if (pet.class === 'cat') {
                    this.catCount++;
                } else {
                    this.dogCount++;
                }

                const emoji = pet.class === 'cat' ? '🐱' : '🐶';
                if (newPet.recognizedName) {
                    addToLog(`${emoji} Welcome back, ${newPet.recognizedName}!`, 'success');
                } else {
                    addToLog(`${emoji} New ${color} ${pet.class} arrived`, 'detection');
                }

                const attempts = this.recognitionAttempts.get(petId) || 0;
                this.recognitionAttempts.set(petId, attempts + 1);

                if (attempts === 3 && !newPet.recognizedName) {
                    addToLog(`💡 Tip: Click Save Pet to name this ${color} ${pet.class}!`, 'info');
                }
            } else {
                const existing = this.activePets.get(petId);
                existing.lastSeen = now;
                existing.bbox = pet.bbox;
                existing.score = pet.score;

                // Update snapshot periodically
                if (imageData && Math.random() < 0.1) { // 10% chance to update snapshot
                    this.takePetSnapshot(petId, imageData, pet.bbox);
                }
            }
        }

        for (const [id, pet] of this.activePets) {
            if (!currentPetIds.has(id) && (now - pet.lastSeen) > CONFIG.PET_TIMEOUT) {
                const emoji = pet.type === 'cat' ? '🐱' : '🐶';
                if (pet.recognizedName) {
                    addToLog(`${emoji} ${pet.recognizedName} left`, 'info');
                } else {
                    addToLog(`${emoji} ${pet.color} ${pet.type} left`, 'info');
                }

                if (pet.type === 'cat') {
                    this.catCount = Math.max(0, this.catCount - 1);
                } else {
                    this.dogCount = Math.max(0, this.dogCount - 1);
                }

                this.activePets.delete(id);
                this.petSnapshots.delete(id);
            }
        }

        this.updateUI();
    }

    saveCurrentPet(name, petId) {
        const pet = this.activePets.get(petId);
        if (!pet) return null;

        savePetToDatabase(
            petId,
            name,
            pet.type,
            pet.color || 'Unknown'
        );

        pet.recognizedName = name;
        pet.recognizedId = petId;

        return pet;
    }

    updateUI() {
        elements.catCount.textContent = this.catCount;
        elements.dogCount.textContent = this.dogCount;
        elements.nowCount.textContent = this.activePets.size;

        if (this.activePets.size > 0) {
            const petList = [];
            for (const [id, pet] of this.activePets) {
                const emoji = pet.type === 'cat' ? '🐱' : '🐶';
                if (pet.recognizedName) {
                    petList.push(`${emoji} ${pet.recognizedName}`);
                } else {
                    petList.push(`${emoji} ${pet.color} ${pet.type}`);
                }
            }
            if (elements.colorInfo) {
                elements.colorInfo.textContent = `🎨 ${petList.join(' · ')}`;
            }
        } else {
            if (elements.colorInfo) {
                elements.colorInfo.textContent = '🎨 No pets detected';
            }
        }
    }

    reset() {
        this.activePets.clear();
        this.recognitionAttempts.clear();
        this.petSnapshots.clear();
        this.catCount = 0;
        this.dogCount = 0;
        this.updateUI();
        if (elements.colorInfo) {
            elements.colorInfo.textContent = '🎨 No pets detected';
        }
        addToLog('🔄 Counter reset', 'info');
    }
}

// ========== MAIN APPLICATION ==========
class PetApp {
    constructor() {
        this.tracker = new PetTracker();
        this.model = null;
        this.videoStream = null;
        this.videoElement = null;
        this.canvas = null;
        this.ctx = null;
        this.detectionInterval = null;
        this.isActive = false;
        this.selectedPetId = null;

        this.initEventListeners();
    }

    initEventListeners() {
        elements.startBtn.addEventListener('click', () => this.startCamera());
        elements.stopBtn.addEventListener('click', () => this.stopCamera());
        elements.snapshotBtn.addEventListener('click', () => this.takeSnapshot());

        if (elements.clearLogBtn) {
            elements.clearLogBtn.addEventListener('click', () => this.clearLog());
        }

        if (elements.savePetBtn) {
            elements.savePetBtn.addEventListener('click', () => this.showPetSelectionModal());
        }

        if (elements.closeModalBtn) {
            elements.closeModalBtn.addEventListener('click', () => this.closePetSelectionModal());
        }
    }

    showPetSelectionModal() {
        const activePets = Array.from(this.tracker.activePets.values());

        if (activePets.length === 0) {
            alert('No pets detected in the camera!');
            return;
        }

        // If only one pet, save it directly
        if (activePets.length === 1) {
            this.savePet(activePets[0]);
            return;
        }

        // Build the pet grid with images
        let gridHtml = '';
        activePets.forEach((pet, index) => {
            const emoji = pet.type === 'cat' ? '🐱' : '🐶';
            const snapshot = this.tracker.getPetSnapshot(pet.id);

            gridHtml += `
                <div class="pet-card" onclick="app.savePetFromModal('${pet.id}')">
                    <div class="pet-card-image">
                        ${snapshot ? `<img src="${snapshot}" style="width:100%;height:100%;object-fit:cover;">` : `${emoji}`}
                    </div>
                    <div class="pet-card-info">
                        ${pet.recognizedName || `${pet.color} ${pet.type}`}
                    </div>
                    <div class="pet-card-type">
                        ${pet.type === 'cat' ? '🐱 Cat' : '🐶 Dog'}
                    </div>
                </div>
            `;
        });

        elements.petGrid.innerHTML = gridHtml;
        elements.petSelectionModal.style.display = 'flex';
    }

    savePetFromModal(petId) {
        const pet = this.tracker.activePets.get(petId);
        if (pet) {
            this.closePetSelectionModal();
            this.savePet(pet);
        }
    }

    closePetSelectionModal() {
        elements.petSelectionModal.style.display = 'none';
    }

    savePet(pet) {
        const defaultName = `${pet.color} ${pet.type}`;
        const name = prompt(`Enter a name for this ${pet.color} ${pet.type}:`, pet.recognizedName || defaultName);

        if (name && name.trim()) {
            this.tracker.saveCurrentPet(name.trim(), pet.id);
        }
    }

    async startCamera() {
        try {
            addToLog('📹 Starting camera...', 'info');

            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }
            });

            this.videoStream = stream;

            this.videoElement = document.createElement('video');
            this.videoElement.srcObject = stream;
            this.videoElement.autoplay = true;
            this.videoElement.playsInline = true;
            this.videoElement.style.width = '100%';
            this.videoElement.style.height = '100%';
            this.videoElement.style.objectFit = 'cover';

            elements.cameraFeed.innerHTML = '';
            elements.cameraFeed.appendChild(this.videoElement);

            this.canvas = document.createElement('canvas');
            this.canvas.style.position = 'absolute';
            this.canvas.style.top = '0';
            this.canvas.style.left = '0';
            this.canvas.style.width = '100%';
            this.canvas.style.height = '100%';
            this.canvas.style.pointerEvents = 'none';
            elements.cameraFeed.style.position = 'relative';
            elements.cameraFeed.appendChild(this.canvas);

            this.ctx = this.canvas.getContext('2d', { willReadFrequently: true });

            elements.cameraFeed.addEventListener('click', (e) => this.handleCanvasClick(e));

            await new Promise((resolve) => {
                this.videoElement.onloadedmetadata = () => {
                    this.canvas.width = this.videoElement.videoWidth;
                    this.canvas.height = this.videoElement.videoHeight;
                    resolve();
                };
            });

            await this.loadModel();

        } catch (error) {
            addToLog(`❌ Camera error: ${error.message}`, 'error');
        }
    }

    handleCanvasClick(e) {
        if (!this.isActive || this.tracker.activePets.size === 0) return;

        const rect = elements.cameraFeed.getBoundingClientRect();
        const scaleX = this.canvas.width / rect.width;
        const scaleY = this.canvas.height / rect.height;

        const clickX = (e.clientX - rect.left) * scaleX;
        const clickY = (e.clientY - rect.top) * scaleY;

        for (const [id, pet] of this.tracker.activePets) {
            const [x, y, w, h] = pet.bbox;
            if (clickX >= x && clickX <= x + w && clickY >= y && clickY <= y + h) {
                this.savePet(pet);
                return;
            }
        }
    }

    async loadModel() {
        addToLog('🧠 Loading AI model...', 'info');

        try {
            this.model = await cocoSsd.load();
            addToLog('✅ Model ready!', 'success');
            addToLog('💾 Click "Save Pet" to name and save detected pets', 'info');
            addToLog('👆 You can also click directly on any pet to save it', 'info');

            this.isActive = true;
            elements.startBtn.disabled = true;
            elements.stopBtn.disabled = false;
            elements.snapshotBtn.disabled = false;

            if (elements.savePetBtn) elements.savePetBtn.disabled = false;

            elements.detectionStats.style.display = 'flex';

            this.tracker.reset();
            this.startDetection();

        } catch (error) {
            addToLog(`❌ Model failed: ${error.message}`, 'error');
        }
    }

    startDetection() {
        let frameCount = 0;
        let lastFpsUpdate = Date.now();

        this.detectionInterval = setInterval(async () => {
            if (!this.isActive || !this.model || !this.videoElement) return;

            try {
                const predictions = await this.model.detect(this.videoElement);

                const pets = predictions.filter(p =>
                    (p.class === 'cat' || p.class === 'dog') && p.score > CONFIG.CONFIDENCE_THRESHOLD
                );

                if (pets.length > 0) {
                    this.ctx.drawImage(this.videoElement, 0, 0, this.canvas.width, this.canvas.height);
                    const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);

                    this.tracker.update(pets, imageData);
                } else {
                    this.tracker.update([], null);
                }

                this.drawDetections(pets);

                frameCount++;
                const now = Date.now();
                if (now - lastFpsUpdate >= 1000) {
                    elements.fpsCount.textContent = frameCount;
                    frameCount = 0;
                    lastFpsUpdate = now;
                }

            } catch (error) {
                console.error('Detection error:', error);
            }
        }, CONFIG.DETECTION_INTERVAL);
    }

    drawDetections(pets) {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        for (const pet of pets) {
            const [x, y, w, h] = pet.bbox;
            const petId = this.tracker.generatePetId(pet);
            const petData = this.tracker.activePets.get(petId);

            const isCat = pet.class === 'cat';
            const color = isCat ? '#FFD700' : '#10B981';
            const confidence = Math.round(pet.score * 100);

            this.ctx.strokeStyle = color;
            this.ctx.lineWidth = 3;
            this.ctx.strokeRect(x, y, w, h);

            let label;
            if (petData && petData.recognizedName) {
                label = `${isCat ? '🐱' : '🐶'} ${petData.recognizedName}`;
            } else {
                const petColor = petData ? petData.color : 'Unknown';
                label = `${isCat ? '🐱' : '🐶'} ${petColor} ${pet.class}`;
            }

            this.ctx.fillStyle = color;
            this.ctx.font = 'bold 14px Inter';
            const textWidth = this.ctx.measureText(label).width;
            this.ctx.fillRect(x, y - 25, textWidth + 10, 25);

            this.ctx.fillStyle = 'white';
            this.ctx.fillText(label, x + 5, y - 8);

            this.ctx.fillStyle = 'rgba(255,255,255,0.9)';
            this.ctx.font = '10px Inter';
            this.ctx.fillText(`${confidence}%`, x + w - 30, y - 30);

            // Add click instruction
            this.ctx.fillStyle = 'rgba(0,0,0,0.5)';
            this.ctx.font = '10px Inter';
            this.ctx.fillText('👆 Click to save', x + 5, y + h - 5);
        }
    }

    takeSnapshot() {
        if (!this.isActive || !this.videoElement) return;

        const snapshotCanvas = document.createElement('canvas');
        snapshotCanvas.width = this.videoElement.videoWidth;
        snapshotCanvas.height = this.videoElement.videoHeight;
        const snapshotCtx = snapshotCanvas.getContext('2d');

        snapshotCtx.drawImage(this.videoElement, 0, 0);
        snapshotCtx.drawImage(this.canvas, 0, 0);

        const timestamp = new Date().toLocaleString();
        snapshotCtx.fillStyle = 'rgba(0,0,0,0.7)';
        snapshotCtx.fillRect(10, 10, 300, 120);
        snapshotCtx.fillStyle = 'white';
        snapshotCtx.font = '12px Inter';
        snapshotCtx.fillText(timestamp, 20, 30);
        snapshotCtx.fillText(`Cats: ${this.tracker.catCount}`, 20, 50);
        snapshotCtx.fillText(`Dogs: ${this.tracker.dogCount}`, 20, 70);
        snapshotCtx.fillText(`Now: ${this.tracker.activePets.size} pets`, 20, 90);

        const link = document.createElement('a');
        link.download = `pets-${Date.now()}.png`;
        link.href = snapshotCanvas.toDataURL('image/png');
        link.click();

        addToLog('📸 Snapshot saved', 'success');
    }

    stopCamera() {
        if (this.videoStream) {
            this.videoStream.getTracks().forEach(t => t.stop());
        }

        if (this.detectionInterval) {
            clearInterval(this.detectionInterval);
        }

        this.isActive = false;

        elements.cameraFeed.innerHTML = `
            <div class="camera-placeholder">
                <span class="placeholder-icon">📷</span>
                <p>Camera stopped</p>
                <p class="placeholder-sub">Click Start Camera</p>
            </div>
        `;

        elements.startBtn.disabled = false;
        elements.stopBtn.disabled = true;
        elements.snapshotBtn.disabled = true;

        if (elements.savePetBtn) elements.savePetBtn.disabled = true;

        elements.detectionStats.style.display = 'none';
        this.closePetSelectionModal();

        addToLog('⏹️ Camera stopped', 'info');

        setTimeout(() => {
            loadSavedPets();
        }, 500);
    }

    clearLog() {
        if (elements.detectionLog) {
            elements.detectionLog.innerHTML = '';
            addToLog('📝 Log cleared', 'info');
        }
    }
}

// ========== LOGGING ==========
function addToLog(message, type) {
    if (!elements.detectionLog) return;

    const time = new Date().toLocaleTimeString();
    const entry = document.createElement('div');
    entry.className = `log-entry ${type}`;
    entry.innerHTML = `
        <span class="log-time">${time}</span>
        <span class="log-message">${message}</span>
    `;

    elements.detectionLog.insertBefore(entry, elements.detectionLog.firstChild);

    while (elements.detectionLog.children.length > 20) {
        elements.detectionLog.removeChild(elements.detectionLog.lastChild);
    }
}

// ========== START ==========
document.addEventListener('DOMContentLoaded', () => {
    window.app = new PetApp();
    window.app.loadSavedPets = loadSavedPets; // Make loadSavedPets accessible

    setTimeout(() => {
        loadSavedPets();
    }, 500);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            console.log('📱 Page became visible, refreshing saved pets...');
            loadSavedPets();
        }
    });

    setInterval(() => {
        fetch(`${API_URL}?action=get`, {
            credentials: 'include'
        })
            .then(response => response.json())
            .catch(() => {
                window.location.href = 'login.php';
            });
    }, 60000);

    addToLog('🚀 Pet Recognition Ready', 'info');
    addToLog('💾 Click "Save Pet" or click directly on any pet to save', 'info');
});

// Make app accessible globally for modal
window.app = null;

// Manual refresh function
window.refreshSavedPets = function () {
    console.log('🔄 Manual refresh triggered');
    loadSavedPets();
    addToLog('🔄 Refreshed saved pets list', 'info');
};

window.manualReset = () => {
    if (window.app) {
        window.app.tracker.reset();
        addToLog('🔄 Manual reset', 'info');
    }
};
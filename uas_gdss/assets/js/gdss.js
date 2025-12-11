/**
 * GDSS JavaScript Functions - Maintain.ly Theme Implementation
 * Handle TOPSIS and BORDA calculations with Gen Z Dark UI
 */

// Dynamic API URL based on current location
const API_BASE_URL = (function() {
    const path = window.location.pathname;
    // Check if we are in views subdirectory or root
    if (path.includes('/views/')) {
        return '../../api/handler.php';
    } else if (path.includes('/Maintain.ly/')) {
        return '../api/handler.php';
    } else {
        return 'api/handler.php';
    }
})();

// Theme Utilities
const GDSS = {
    theme: {
        colors: {
            primary: '#0ea5e9',
            secondary: '#10b981',
            accent: '#06b6d4',
            dark: '#0f172a',
            darker: '#020617'
        },
        animations: {
            duration: 300,
            ease: 'cubic-bezier(0.4, 0, 0.2, 1)'
        }
    },
    
    // Initialize theme components
    init() {
        this.setupAnimations();
        this.setupLoadingStates();
        this.setupHoverEffects();
        this.setupAlerts();
    },
    
    // Setup loading animations
    setupLoadingStates() {
        document.addEventListener('DOMContentLoaded', () => {
            // Auto dismiss alerts
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    if (alert.classList.contains('auto-dismiss')) {
                        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                        bsAlert.close();
                    }
                });
            }, 5000);
            
            // Initialize loading spinners
            this.initLoadingSpinners();
        });
    },
    
    // Initialize loading spinners with double animation
    initLoadingSpinners() {
        const spinners = document.querySelectorAll('.loading-spinner');
        spinners.forEach(spinner => {
            spinner.innerHTML = `
                <div class="relative inline-block w-16 h-16">
                    <div class="animate-spin rounded-full h-16 w-16 border-4 border-cyan-500/30 border-t-cyan-500"></div>
                    <div class="absolute inset-0 animate-ping rounded-full h-16 w-16 border-2 border-cyan-500/20"></div>
                </div>
                <div class="mt-4 text-slate-400 text-sm font-bold uppercase tracking-wider animate-pulse">LOADING...</div>
            `;
        });
    },
    
    // Setup hover effects
    setupHoverEffects() {
        // Card hover effects
        const cards = document.querySelectorAll('.card-hover');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-8px) scale(1.02)';
                card.style.boxShadow = '0 25px 50px -12px rgba(0, 0, 0, 0.5)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'none';
                card.style.boxShadow = 'none';
            });
        });
    },
    
    // Setup alert system
    setupAlerts() {
        // Auto-dismiss functionality
        const dismissibleAlerts = document.querySelectorAll('.alert-dismissible');
        dismissibleAlerts.forEach(alert => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                });
            }
        });
    },
    
    // Setup animations
    setupAnimations() {
        // Intersection Observer for slide-in animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-slide-in');
                }
            });
        }, observerOptions);
        
        // Observe elements that should animate in
        const animateElements = document.querySelectorAll('.animate-on-scroll');
        animateElements.forEach(el => observer.observe(el));
    }
};

// Initialize theme on load
document.addEventListener('DOMContentLoaded', () => {
    GDSS.init();
});

// Calculate TOPSIS for current user's field - Gen Z Style
function calculateTOPSIS() {
    if (!showConfirm('TOPSIS CALCULATION', 'Apakah Anda yakin ingin menjalankan perhitungan TOPSIS? Pastikan semua evaluasi sudah selesai.')) {
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    setButtonLoading(button, 'CALCULATING...');
    button.disabled = true;
    
    fetch(API_BASE_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=calculate_topsis'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            }
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Terjadi kesalahan sistem');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Calculate BORDA consensus (supervisor only)
function calculateBorda() {
    if (!confirm('Apakah Anda yakin ingin menjalankan konsensus BORDA? Pastikan semua perhitungan TOPSIS sudah selesai.')) {
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Menghitung Konsensus...';
    button.disabled = true;
    
    fetch(API_BASE_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=calculate_borda'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            }
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Terjadi kesalahan sistem');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Show alert message
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Preview TOPSIS calculation
function previewTOPSIS() {
    const projectId = document.querySelector('input[name="project_id"]')?.value;
    if (!projectId) {
        showAlert('warning', 'Pilih proyek terlebih dahulu');
        return;
    }
    
    fetch(API_BASE_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=preview_topsis&project_id=${projectId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('topsisPreviewContent').innerHTML = data.html;
            const modal = new bootstrap.Modal(document.getElementById('topsisPreviewModal'));
            modal.show();
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Terjadi kesalahan sistem');
    });
}

// Load TOPSIS results
function loadTOPSISResults() {
    fetch(API_BASE_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_topsis_results'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayTOPSISResults(data.results);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Gagal memuat hasil TOPSIS');
    });
}

// Display TOPSIS results in table
function displayTOPSISResults(results) {
    const tbody = document.querySelector('#topsisResultsTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (results && results.length > 0) {
        results.forEach(result => {
            const row = tbody.insertRow();
            row.innerHTML = `
                <td><span class="badge bg-info">${result.project_code}</span></td>
                <td>${result.project_name}</td>
                <td>${parseFloat(result.topsis_score).toFixed(4)}</td>
                <td><span class="badge bg-primary">${result.rank}</span></td>
                <td><small class="text-muted">${result.calculation_date}</small></td>
            `;
        });
    } else {
        const row = tbody.insertRow();
        row.innerHTML = '<td colspan="5" class="text-center text-muted">Belum ada hasil TOPSIS</td>';
    }
}

// Load BORDA results (supervisor only)
function loadBordaResults() {
    fetch(API_BASE_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_borda_results'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayBordaResults(data.results);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Gagal memuat hasil BORDA');
    });
}

// Display BORDA results
function displayBordaResults(results) {
    const tbody = document.querySelector('#bordaResultsTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (results && results.length > 0) {
        results.forEach(result => {
            const row = tbody.insertRow();
            row.innerHTML = `
                <td><span class="badge bg-warning">${result.final_rank}</span></td>
                <td><span class="badge bg-info">${result.project_code}</span></td>
                <td>${result.project_name}</td>
                <td>${parseFloat(result.borda_score).toFixed(2)}</td>
                <td>${result.location}</td>
            `;
        });
    } else {
        const row = tbody.insertRow();
        row.innerHTML = '<td colspan="5" class="text-center text-muted">Belum ada hasil konsensus BORDA</td>';
    }
}

// Maintain.ly Theme Utilities
function setButtonLoading(button, text = 'LOADING...') {
    const originalHtml = button.innerHTML;
    button.innerHTML = `
        <div class="inline-flex items-center">
            <div class="animate-spin rounded-full h-4 w-4 border-2 border-slate-900/30 border-t-slate-900 mr-2"></div>
            <div class="animate-ping rounded-full h-4 w-4 border border-slate-900/20 absolute"></div>
            <span class="font-black uppercase tracking-wider">${text}</span>
        </div>
    `;
    button.classList.add('opacity-80', 'cursor-not-allowed');
    return originalHtml;
}

function resetButton(button, originalText) {
    button.innerHTML = originalText;
    button.classList.remove('opacity-80', 'cursor-not-allowed');
}

function showGlowAlert(type, message, duration = 5000) {
    const alertTypes = {
        success: {
            bg: 'rgba(16, 185, 129, 0.1)',
            border: '#10b981',
            text: '#10b981',
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                   </svg>`
        },
        danger: {
            bg: 'rgba(239, 68, 68, 0.1)',
            border: '#ef4444',
            text: '#ef4444',
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                   </svg>`
        },
        warning: {
            bg: 'rgba(245, 158, 11, 0.1)',
            border: '#f59e0b',
            text: '#f59e0b',
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                   </svg>`
        },
        info: {
            bg: 'rgba(6, 182, 212, 0.1)',
            border: '#06b6d4',
            text: '#06b6d4',
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                   </svg>`
        }
    };
    
    const config = alertTypes[type] || alertTypes.info;
    
    const alertElement = document.createElement('div');
    alertElement.className = 'fixed top-4 right-4 z-50 max-w-sm animate-slide-in';
    alertElement.style.cssText = `
        background: ${config.bg};
        backdrop-filter: blur(10px);
        border: 1px solid ${config.border};
        color: ${config.text};
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        font-weight: 600;
    `;
    
    alertElement.innerHTML = `
        <div class="flex items-center">
            ${config.icon}
            <span class="ml-3 font-bold uppercase tracking-wide text-sm">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-current opacity-70 hover:opacity-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(alertElement);
    
    // Auto remove after duration
    setTimeout(() => {
        if (alertElement.parentNode) {
            alertElement.style.opacity = '0';
            alertElement.style.transform = 'translateY(-10px)';
            setTimeout(() => alertElement.remove(), 300);
        }
    }, duration);
}

function showConfirm(title, message) {
    return confirm(`${title}\n\n${message.toUpperCase()}`);
}

// Auto load results on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load TOPSIS results if on TOPSIS results page
    if (document.querySelector('#topsisResultsTable')) {
        loadTOPSISResults();
    }
    
    // Load BORDA results if on BORDA results page
    if (document.querySelector('#bordaResultsTable')) {
        loadBordaResults();
    }
    
    // Setup menu highlighting
    const currentPath = window.location.pathname.split('/').pop();
    const menuLinks = document.querySelectorAll('.sidebar .nav-link');
    
    menuLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        if (linkPath && linkPath.includes(currentPath)) {
            link.classList.add('active');
        }
    });
});
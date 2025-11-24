/**
 * Admin Dashboard JavaScript Components
 * Handles charts, tables, modals, and interactive features
 */

// Chart.js Configuration
const chartColors = {
    primary: '#C67D3B',
    primaryDark: '#A86830',
    secondary: '#E8C89F',
    accent: '#FFD23F',
    success: '#10B981',
    warning: '#F59E0B',
    danger: '#EF4444',
    info: '#3B82F6',
    gray: '#9CA3AF'
};

// Initialize Charts
function initCharts() {
    // Sales Chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Revenue',
                    data: [12000, 19000, 15000, 25000, 22000, 30000, 28000, 32000, 35000, 38000, 42000, 45000],
                    borderColor: chartColors.primary,
                    backgroundColor: 'rgba(198, 125, 59, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Order Status Chart
    const orderStatusCtx = document.getElementById('orderStatusChart');
    if (orderStatusCtx) {
        new Chart(orderStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Delivered', 'In Transit', 'Pending', 'Cancelled'],
                datasets: [{
                    data: [450, 120, 85, 35],
                    backgroundColor: [
                        chartColors.success,
                        chartColors.info,
                        chartColors.warning,
                        chartColors.danger
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Peak Hours Chart
    const peakHoursCtx = document.getElementById('peakHoursChart');
    if (peakHoursCtx) {
        new Chart(peakHoursCtx, {
            type: 'bar',
            data: {
                labels: ['6AM', '9AM', '12PM', '3PM', '6PM', '9PM', '12AM'],
                datasets: [{
                    label: 'Orders',
                    data: [45, 89, 156, 98, 234, 189, 67],
                    backgroundColor: chartColors.primary
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
}

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal on overlay click
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        closeModal(e.target.id);
    }
});

// Sidebar Toggle
function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const main = document.querySelector('.admin-main');

    if (window.innerWidth <= 1024) {
        sidebar.classList.toggle('mobile-open');

        // Handle overlay
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            overlay.addEventListener('click', toggleSidebar);
            document.body.appendChild(overlay);
        }
        overlay.classList.toggle('active');
    } else {
        sidebar.classList.toggle('collapsed');
        main.classList.toggle('sidebar-collapsed');
    }
}

// Search Functionality
function initSearch() {
    const searchInputs = document.querySelectorAll('[data-search-table]');

    searchInputs.forEach(input => {
        const tableId = input.dataset.searchTable;
        const table = document.getElementById(tableId);

        if (table) {
            input.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }
    });
}

// Filter Functionality
function filterTable(tableId, column, value) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const cell = row.cells[column];
        if (!cell) return;

        if (value === 'all' || cell.textContent.toLowerCase().includes(value.toLowerCase())) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// AJAX Form Submission
function submitForm(formId, successCallback) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(form);
        const submitBtn = form.querySelector('[type="submit"]');

        // Disable submit button
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', result.message || 'Operation successful!');
                if (successCallback) successCallback(result);
            } else {
                showAlert('danger', result.message || 'Operation failed!');
            }
        } catch (error) {
            showAlert('danger', 'An error occurred. Please try again.');
            console.error(error);
        } finally {
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit';
            }
        }
    });
}

// Show Alert
function showAlert(type, message) {
    const alertContainer = document.getElementById('alertContainer') || createAlertContainer();

    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;margin-left:auto;font-size:1.25rem;">&times;</button>
    `;

    alertContainer.appendChild(alert);

    // Auto remove after 5 seconds
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alertContainer';
    container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:10000;max-width:400px;';
    document.body.appendChild(container);
    return container;
}

// Confirm Action
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Delete Item
async function deleteItem(endpoint, id, itemName = 'item') {
    if (!confirm(`Are you sure you want to delete this ${itemName}?`)) {
        return;
    }

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id, action: 'delete' })
        });

        const result = await response.json();

        if (result.success) {
            showAlert('success', `${itemName} deleted successfully!`);
            // Reload page or remove row
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('danger', result.message || 'Failed to delete item');
        }
    } catch (error) {
        showAlert('danger', 'An error occurred');
        console.error(error);
    }
}

// Update Status
async function updateStatus(endpoint, id, status) {
    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id, status: status })
        });

        const result = await response.json();

        if (result.success) {
            showAlert('success', 'Status updated successfully!');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('danger', result.message || 'Failed to update status');
        }
    } catch (error) {
        showAlert('danger', 'An error occurred');
        console.error(error);
    }
}

// Export Table to CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => {
            return '"' + col.textContent.replace(/"/g, '""') + '"';
        });
        csv.push(rowData.join(','));
    });

    // Download CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Real-time Updates (Polling)
let updateInterval;

function startRealTimeUpdates(endpoint, updateCallback, interval = 30000) {
    updateInterval = setInterval(async () => {
        try {
            const response = await fetch(endpoint);
            const data = await response.json();
            updateCallback(data);
        } catch (error) {
            console.error('Update failed:', error);
        }
    }, interval);
}

function stopRealTimeUpdates() {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
}

// Format Currency
function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Format Date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Initialize DataTables (if library is loaded)
function initDataTables() {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('.data-table-enhanced').DataTable({
            pageLength: 25,
            responsive: true,
            order: [[0, 'desc']],
            language: {
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: 'Next',
                    previous: 'Previous'
                }
            }
        });
    }
}

// Image Preview
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };

        reader.readAsDataURL(input.files[0]);
    }
}

// Copy to Clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('success', 'Copied to clipboard!');
    }).catch(err => {
        showAlert('danger', 'Failed to copy');
    });
}

// Initialize on DOM Load
document.addEventListener('DOMContentLoaded', function () {
    // Initialize charts if Chart.js is loaded
    if (typeof Chart !== 'undefined') {
        initCharts();
    }

    // Initialize search
    initSearch();

    // Initialize DataTables
    initDataTables();

    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleSidebar);
    }

    // Close mobile sidebar on link click
    if (window.innerWidth <= 1024) {
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                const sidebar = document.querySelector('.admin-sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                sidebar.classList.remove('mobile-open');
                if (overlay) {
                    overlay.classList.remove('active');
                }
            });
        });
    }
});

// Export functions for global use
window.adminUtils = {
    openModal,
    closeModal,
    toggleSidebar,
    filterTable,
    submitForm,
    showAlert,
    confirmAction,
    deleteItem,
    updateStatus,
    exportTableToCSV,
    startRealTimeUpdates,
    stopRealTimeUpdates,
    formatCurrency,
    formatDate,
    previewImage,
    copyToClipboard
};

// Mobile Swipe Gesture Support
if (window.innerWidth <= 1024) {
    let touchStartX = 0;
    let touchEndX = 0;
    let touchStartY = 0;
    let touchEndY = 0;

    document.addEventListener('touchstart', function (e) {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });

    document.addEventListener('touchend', function (e) {
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
        handleSwipe();
    }, { passive: true });

    function handleSwipe() {
        const sidebar = document.querySelector('.admin-sidebar');
        const swipeThreshold = 50;
        const horizontalSwipe = Math.abs(touchEndX - touchStartX);
        const verticalSwipe = Math.abs(touchEndY - touchStartY);

        // Only handle horizontal swipes (ignore vertical scrolling)
        if (horizontalSwipe > verticalSwipe && horizontalSwipe > swipeThreshold) {
            // Swipe right from left edge to open
            if (touchEndX > touchStartX && touchStartX < 50 && !sidebar.classList.contains('mobile-open')) {
                toggleSidebar();
            }
            // Swipe left to close
            else if (touchEndX < touchStartX && sidebar.classList.contains('mobile-open')) {
                toggleSidebar();
            }
        }
    }
}

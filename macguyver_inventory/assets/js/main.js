// MacGuyver Inventory - Main JS

// Sidebar Toggle
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // Auto-dismiss alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(a => {
            a.style.transition = 'opacity .5s';
            a.style.opacity = '0';
            setTimeout(() => a.remove(), 500);
        });
    }, 4000);

    // Confirm delete
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm('Are you sure you want to delete this item? This cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
});

// Modal functions
function openModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.add('open');
}
function closeModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.remove('open');
}
// Close modal on outside click
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
    }
});

// Search table
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    input.addEventListener('keyup', () => {
        const filter = input.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
        });
    });
}

// Print page
function printPage() { window.print(); }

// js/collaborations.js - AJAX search for collaborations

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('live-search');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            const searchTerm = this.value;
            if (searchTerm.length >= 2 || searchTerm.length === 0) {
                searchCollaborations(searchTerm);
            }
        });
    }
});

function searchCollaborations(searchTerm) {
    fetch(`../staff/collaborations/search.php?q=${encodeURIComponent(searchTerm)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCollaborationTable(data.results);
        }
    })
    .catch(error => console.error('Error:', error));
}

function updateCollaborationTable(collaborations) {
    const tbody = document.querySelector('.data-table tbody');
    if (!tbody) return;
    
    if (collaborations.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No collaborations found</td></tr>';
        return;
    }
    
    let html = '';
    collaborations.forEach(collab => {
        html += `
            <tr>
                <td><strong>${escapeHtml(collab.partner_name)}</strong></td>
                <td>${capitalize(collab.partner_type)}</td>
                <td>${escapeHtml(collab.agreement_type || '-')}</td>
                <td>${formatPeriod(collab.start_date, collab.end_date)}</td>
                <td><span class="status-badge status-${collab.status}">${capitalize(collab.status)}</span></td>
                <td>${escapeHtml(collab.contact_person || '-')}</td>
                <td>
                    <a href="edit.php?id=${collab.collab_id}" class="btn btn-sm">Edit</a>
                    <a href="delete.php?id=${collab.collab_id}" class="btn btn-sm btn-danger" onclick="return confirmDelete()">Delete</a>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
}

function formatPeriod(startDate, endDate) {
    if (!startDate) return '-';
    const start = new Date(startDate).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    if (!endDate) return start;
    const end = new Date(endDate).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    return `${start} - ${end}`;
}
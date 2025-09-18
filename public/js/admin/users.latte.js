/**
 * CSP-konforme Event-Handler für admin/users.latte
 * Ersetzt alle inline onclick-Handler durch Event-Delegation
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Messages aus data-Attributen laden
    const messages = document.getElementById('users-messages');
    const msgConfirmDelete = messages?.dataset.confirmDelete || 'Are you sure you want to delete this user?';
    const msgDeleteSuccess = messages?.dataset.deleteSuccess || 'User deleted successfully';
    const msgToggleSuccess = messages?.dataset.toggleSuccess || 'Status updated successfully';
    const msgToggleError = messages?.dataset.toggleError || 'Error updating status';
    const msgAccountActivated = messages?.dataset.accountActivated || 'User account activated';
    const msgAccountDeactivated = messages?.dataset.accountDeactivated || 'User account deactivated';
    const msg2faEnabled = messages?.dataset['2faEnabled'] || '2FA enabled successfully';
    const msg2faDisabled = messages?.dataset['2faDisabled'] || '2FA disabled successfully';
    const msg2faEnforced = messages?.dataset['2faEnforced'] || '2FA enforcement enabled';
    const msg2faUnenforced = messages?.dataset['2faUnenforced'] || '2FA enforcement disabled';

    // DOM-Elemente
    const loadingIndicator = document.getElementById('loadingIndicator');
    const userTableBody = document.getElementById('userTableBody');
    const confirmDeleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    const deleteUserId = document.getElementById('deleteUserId');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    // Event Delegation für alle User-Actions
    document.addEventListener('click', function(e) {
        const target = e.target.closest('[data-action]');
        if (!target) return;

        const action = target.dataset.action;
        const userId = target.dataset.userId;
        const operation = target.dataset.operation;
        const username = target.dataset.username;
        
        switch(action) {
            case 'toggle-account-status':
                toggleUserAccountStatus(userId, operation);
                break;
            case 'toggle-mfa':
                toggleMfa(userId, operation);
                break;
            case 'toggle-mfa-enforcement':
                toggleMfaEnforcement(userId, operation);
                break;
            case 'delete-user':
                showDeleteConfirmation(userId, username);
                break;
            case 'confirm-delete':
                deleteUser();
                break;
            case 'create-user':
                window.location.href = '/admin/showCreateUser';
                break;
        }
    });

    // Event-Handler für Page-Size-Änderung
    document.addEventListener('change', function(e) {
        if (e.target.dataset.action === 'change-page-size') {
            e.target.closest('form').submit();
        }
    });

    /**
     * Account Status Toggle Function
     */
    function toggleUserAccountStatus(userId, operation) {
        if (!userId || !operation) return;
        
        const url = operation === 'enable' ? '/admin/enableUser' : '/admin/disableUser';
        const formData = new FormData();
        formData.append('id', userId);
        formData.append('csrf_token', document.getElementById('csrf_token').value);
        
        showLoading(true);
        
        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateAccountStatusIcon(userId, operation);
                const message = operation === 'enable' ? msgAccountActivated : msgAccountDeactivated;
                showToast(message, 'success', 'Success');
            } else {
                showToast(data.message || msgToggleError, 'danger', 'Error');
            }
        })
        .catch(error => {
            console.error('Error toggling account status:', error);
            showToast(msgToggleError, 'danger', 'Error');
        })
        .finally(() => {
            showLoading(false);
        });
    }

    /**
     * 2FA Toggle Function
     */
    function toggleMfa(userId, operation) {
        if (!userId || !operation) return;
        
        const url = operation === 'enable' ? '/admin/enableMfa' : '/admin/disableMfa';
        const formData = new FormData();
        formData.append('id', userId);
        formData.append('csrf_token', document.getElementById('csrf_token').value);
        
        showLoading(true);
        
        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateMfaIcon(userId, operation);
                const message = operation === 'enable' ? msg2faEnabled : msg2faDisabled;
                showToast(message, 'success', 'Success');
            } else {
                showToast(data.message || msgToggleError, 'danger', 'Error');
            }
        })
        .catch(error => {
            console.error('Error toggling 2FA:', error);
            showToast(msgToggleError, 'danger', 'Error');
        })
        .finally(() => {
            showLoading(false);
        });
    }

    /**
     * 2FA Enforcement Toggle Function
     */
    function toggleMfaEnforcement(userId, operation) {
        if (!userId || !operation) return;
        
        const url = operation === 'enable' ? '/admin/enforceMfa' : '/admin/unenforceMfa';
        const formData = new FormData();
        formData.append('id', userId);
        formData.append('csrf_token', document.getElementById('csrf_token').value);
        
        showLoading(true);
        
        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateMfaEnforcementIcon(userId, operation);
                const message = operation === 'enable' ? msg2faEnforced : msg2faUnenforced;
                showToast(message, 'success', 'Success');
            } else {
                showToast(data.message || msgToggleError, 'danger', 'Error');
            }
        })
        .catch(error => {
            console.error('Error toggling 2FA enforcement:', error);
            showToast(msgToggleError, 'danger', 'Error');
        })
        .finally(() => {
            showLoading(false);
        });
    }

    /**
     * Show Delete Confirmation Modal
     */
    function showDeleteConfirmation(userId, username) {
        if (!userId) return;
        
        deleteUserId.value = userId;
        const modalBody = document.querySelector('#confirmDeleteModal .modal-body p');
        modalBody.textContent = `${msgConfirmDelete} "${username}"?`;
        
        confirmDeleteModal.show();
    }

    /**
     * Delete User Function
     */
    function deleteUser() {
        const userId = deleteUserId.value;
        if (!userId) return;
        
        const formData = new FormData();
        formData.append('id', userId);
        formData.append('csrf_token', document.getElementById('csrf_token').value);
        
        // Disable delete button
        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
        
        fetch('/admin/deleteUser', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(msgDeleteSuccess, 'success', 'Success');
                confirmDeleteModal.hide();
                // Refresh page after short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast(data.message || 'Error deleting user', 'danger', 'Error');
            }
        })
        .catch(error => {
            console.error('Error deleting user:', error);
            showToast('Error deleting user', 'danger', 'Error');
        })
        .finally(() => {
            // Restore delete button
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.innerHTML = '<i class="bi-trash-fill"></i> Delete';
        });
    }

    /**
     * Update Account Status Icon (Desktop + Mobile)
     */
    function updateAccountStatusIcon(userId, operation) {
        // Desktop icons
        const desktopIcon = document.getElementById(`accountStatus${userId}`);
        // Mobile icons
        const mobileIcon = document.getElementById(`maccountStatus${userId}`);
        
        [desktopIcon, mobileIcon].forEach(icon => {
            if (!icon) return;
            
            if (operation === 'enable') {
                // Change to enabled state
                icon.className = 'bi-check-circle-fill text-success user-action-btn';
                icon.dataset.operation = 'disable';
                icon.title = icon.dataset.bsOriginalTitle = 'Disable Useraccount';
            } else {
                // Change to disabled state
                icon.className = 'bi-x-circle-fill text-danger user-action-btn';
                icon.dataset.operation = 'enable';
                icon.title = icon.dataset.bsOriginalTitle = 'Enable Useraccount';
            }
            
            // Update tooltip
            const tooltip = bootstrap.Tooltip.getInstance(icon);
            if (tooltip) {
                tooltip.dispose();
                new bootstrap.Tooltip(icon);
            }
        });
    }

    /**
     * Update 2FA Icon (Desktop + Mobile)
     */
    function updateMfaIcon(userId, operation) {
        // Desktop icons
        const desktopIcon = document.getElementById(`mfaEnabled${userId}`);
        // Mobile icons  
        const mobileIcon = document.getElementById(`mmfaEnabled${userId}`);
        
        [desktopIcon, mobileIcon].forEach(icon => {
            if (!icon) return;
            
            if (operation === 'enable') {
                // Change to enabled state
                icon.className = 'bi-check-circle-fill text-success user-action-btn';
                icon.dataset.operation = 'disable';
                icon.title = icon.dataset.bsOriginalTitle = 'Disable 2FA';
            } else {
                // Change to disabled state
                icon.className = 'bi-x-circle-fill text-danger user-action-btn';
                icon.dataset.operation = 'enable';
                icon.title = icon.dataset.bsOriginalTitle = 'Enable 2FA';
            }
            
            // Update tooltip
            const tooltip = bootstrap.Tooltip.getInstance(icon);
            if (tooltip) {
                tooltip.dispose();
                new bootstrap.Tooltip(icon);
            }
        });
    }

    /**
     * Update 2FA Enforcement Icon (Desktop + Mobile)
     */
    function updateMfaEnforcementIcon(userId, operation) {
        // Desktop icons
        const desktopIcon = document.getElementById(`enforceStatus${userId}`);
        // Mobile icons
        const mobileIcon = document.getElementById(`menforceStatus${userId}`);
        
        [desktopIcon, mobileIcon].forEach(icon => {
            if (!icon) return;
            
            if (operation === 'enable') {
                // Change to enforced state
                icon.className = 'bi-check-circle-fill text-warning user-action-btn';
                icon.dataset.operation = 'disable';
                icon.title = icon.dataset.bsOriginalTitle = 'Click to unenforce 2FA for user';
            } else {
                // Change to unenforced state
                icon.className = 'bi-x-circle-fill text-danger user-action-btn';
                icon.dataset.operation = 'enable';
                icon.title = icon.dataset.bsOriginalTitle = 'Click to enforce 2FA for user';
            }
            
            // Update tooltip
            const tooltip = bootstrap.Tooltip.getInstance(icon);
            if (tooltip) {
                tooltip.dispose();
                new bootstrap.Tooltip(icon);
            }
        });
    }

    /**
     * Show/Hide Loading Indicator
     */
    function showLoading(show) {
        if (loadingIndicator) {
            loadingIndicator.style.display = show ? 'block' : 'none';
        }
    }

    /**
     * Initialize Tooltips
     */
    function initializeTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    /**
     * AJAX User Fetching (for search functionality)
     */
    function fetchUsers(searchTerm = '') {
        showLoading(true);
        userTableBody.innerHTML = '';
        
        fetch(`/admin/users?search=${encodeURIComponent(searchTerm)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.users && data.users.length > 0) {
                renderUsers(data.users);
            } else {
                renderNoUsers();
            }
            initializeTooltips();
        })
        .catch(error => {
            console.error('Error fetching users:', error);
            showToast('Error loading users', 'danger', 'Error');
        })
        .finally(() => {
            showLoading(false);
        });
    }

    /**
     * Render Users in Table (for AJAX updates)
     */
    function renderUsers(users) {
        let html = '';
        
        users.forEach(user => {
            html += `
                <tr>
                    <td>
                        ${user.ldapEnabled === 1 ? 
                            '<i class="bi-hdd-network-fill text-teal" data-bs-toggle="tooltip" title="LDAP User"></i>' : 
                            '<i class="bi-database-fill text-indigo" data-bs-toggle="tooltip" title="Database User"></i>'
                        }
                    </td>
                    <td style="min-width:40px !important;text-align:center;">
                        ${user.username === 'super.admin' ?
                            '<i class="bi-check-circle-fill text-secondary" data-bs-toggle="tooltip" title="Cannot disable super admin"></i>' :
                            user.status === 1 ?
                                `<i id="accountStatus${user.id}" class="bi-check-circle-fill text-success user-action-btn" 
                                   data-action="toggle-account-status" data-user-id="${user.id}" data-operation="disable"
                                   style="cursor:pointer;" data-bs-toggle="tooltip" title="Disable User"></i>` :
                                `<i id="accountStatus${user.id}" class="bi-x-circle-fill text-danger user-action-btn" 
                                   data-action="toggle-account-status" data-user-id="${user.id}" data-operation="enable"
                                   style="cursor:pointer;" data-bs-toggle="tooltip" title="Enable User"></i>`
                        }
                    </td>
                    <td style="min-width:40px !important;text-align:center;">
                        ${user.mfaSecret !== '' && user.mfaEnabled === 1 ?
                            `<i id="mfaEnabled${user.id}" class="bi-check-circle-fill text-success user-action-btn" 
                               data-action="toggle-mfa" data-user-id="${user.id}" data-operation="disable"
                               data-bs-toggle="tooltip" title="Disable 2FA" style="cursor:pointer;"></i>` :
                            user.mfaSecret !== '' && user.mfaEnabled === 0 ?
                                `<i id="mfaEnabled${user.id}" class="bi-x-circle-fill text-danger user-action-btn" 
                                   data-action="toggle-mfa" data-user-id="${user.id}" data-operation="enable"
                                   data-bs-toggle="tooltip" title="Enable 2FA" style="cursor:pointer;"></i>` :
                                `<i id="mfaEnabled${user.id}" class="bi-x-circle-fill text-secondary" data-bs-toggle="tooltip" title="User has not setup 2FA"></i>`
                        }
                    </td>
                    <td style="min-width:40px !important;text-align:center;">
                        ${user.username === 'super.admin' ?
                            '<i class="bi-check-circle-fill text-secondary" data-bs-toggle="tooltip" title="2FA enforcement cannot be changed for super.admin"></i>' :
                            user.mfaSecret !== '' ?
                                '<i class="bi-check-circle-fill text-secondary" data-bs-toggle="tooltip" title="2FA is configured already"></i>' :
                                user.mfaEnforced === 1 ?
                                    `<i id="enforceStatus${user.id}" class="bi-check-circle-fill text-warning user-action-btn" 
                                       data-action="toggle-mfa-enforcement" data-user-id="${user.id}" data-operation="disable"
                                       style="cursor:pointer;" data-bs-toggle="tooltip" title="Click to unenforce 2FA for user"></i>` :
                                    `<i id="enforceStatus${user.id}" class="bi-x-circle-fill text-danger user-action-btn" 
                                       data-action="toggle-mfa-enforcement" data-user-id="${user.id}" data-operation="enable"
                                       style="cursor:pointer;" data-bs-toggle="tooltip" title="Click to enforce 2FA for user"></i>`
                        }
                    </td>
                    <td>
                        <strong>${user.username}</strong><br>
                        <span class="text-muted">${user.firstname} ${user.lastname}</span><br>
                        <span class="badge bg-info">${user.roles}</span>
                    </td>
                    <td>${user.email}</td>
                    <td class="actions-column">
                        ${user.username === 'super.admin' ?
                            '<button class="btn btn-sm btn-secondary" disabled><i class="bi-trash-fill"></i> Delete</button>' :
                            `<button class="btn btn-sm btn-danger user-action-btn" 
                                     data-action="delete-user" data-user-id="${user.id}" data-username="${user.username}">
                                <i class="bi-trash-fill"></i> Delete
                             </button>`
                        }
                        <a href="/admin/showEditUser/${user.id}" class="btn btn-sm btn-warning">
                            <i class="bi-pencil-fill"></i> Edit
                        </a>
                    </td>
                </tr>
            `;
        });
        
        userTableBody.innerHTML = html;
    }

    /**
     * Render No Users Message
     */
    function renderNoUsers() {
        userTableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <i class="bi bi-person-x text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-2">No users found</h5>
                    <p class="text-muted">Try adjusting your search criteria</p>
                </td>
            </tr>
        `;
    }

    // Initialize tooltips on page load
    initializeTooltips();
    
    // Initialize modal event listeners
    confirmDeleteModal._element.addEventListener('hidden.bs.modal', function () {
        deleteUserId.value = '';
        confirmDeleteBtn.disabled = false;
        confirmDeleteBtn.innerHTML = '<i class="bi-trash-fill"></i> Delete';
    });
    
    // Optional: Real-time search (debounced)
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 2 || this.value.length === 0) {
                    fetchUsers(this.value);
                }
            }, 500);
        });
    }
});
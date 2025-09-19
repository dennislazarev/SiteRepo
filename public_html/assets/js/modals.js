// public_html/assets/js/modals.js
document.addEventListener('DOMContentLoaded', function () {
    // --- Логика для модального окна удаления СОТРУДНИКА ---
    var deleteEmployeeModal = document.getElementById('deleteEmployeeModal');
    if (deleteEmployeeModal) {
        deleteEmployeeModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var itemId = button.getAttribute('data-employee-id');
            var itemDisplayName = button.getAttribute('data-employee-fio');
            var modal = this;
            modal.querySelector('#delete-employee-fio').textContent = itemDisplayName;
            var form = modal.querySelector('#delete-employee-form');
            if (form) {
                form.action = '/admin/employees/' + itemId + '/delete';
            }
        });
    }
    // --- Конец логики для сотрудника ---

    // --- Логика для модального окна удаления РОЛИ сотрудника ---
    var deleteRoleModal = document.getElementById('deleteRoleModal');
    if (deleteRoleModal) {
        deleteRoleModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var itemId = button.getAttribute('data-role-id');
            var itemDisplayName = button.getAttribute('data-role-name');
            var modal = this;
            modal.querySelector('#delete-role-name').textContent = itemDisplayName;
            var form = modal.querySelector('#delete-role-form');
            if (form) {
                form.action = '/admin/roles/' + itemId + '/delete';
            }
        });
    }
    // --- Конец логики для роли сотрудника ---

    // --- Логика для модального окна удаления ПРАВА сотрудника ---
    var deletePermissionModal = document.getElementById('deletePermissionModal');
    if (deletePermissionModal) {
        deletePermissionModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var itemId = button.getAttribute('data-permission-id');
            var itemDisplayName = button.getAttribute('data-permission-name');
            var modal = this;
            modal.querySelector('#delete-permission-name').textContent = itemDisplayName;
            var form = modal.querySelector('#delete-permission-form');
            if (form) {
                form.action = '/admin/permissions/' + itemId + '/delete';
            }
        });
    }
    // --- Конец логики для права сотрудника ---

    // --- Логика для модального окна удаления ПОЛЬЗОВАТЕЛЯ ---
    var deleteUserModal = document.getElementById('deleteUserModal');
    if (deleteUserModal) {
        deleteUserModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var itemId = button.getAttribute('data-user-id');
            var itemDisplayName = button.getAttribute('data-user-fio'); // Или data-user-login
            var modal = this;
            modal.querySelector('#delete-user-fio').textContent = itemDisplayName; // Или #delete-user-login
            var form = modal.querySelector('#delete-user-form');
            if (form) {
                form.action = '/admin/users/' + itemId + '/delete';
            }
        });
    }
    // --- Конец логики для пользователя ---

    // --- Логика для модального окна удаления РОЛИ ПОЛЬЗОВАТЕЛЯ ---
    var deleteUserRoleModal = document.getElementById('deleteUserRoleModal');
    if (deleteUserRoleModal) {
        deleteUserRoleModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var itemId = button.getAttribute('data-role-id');
            var itemDisplayName = button.getAttribute('data-role-display-name'); // Или data-role-name
            var modal = this;
            modal.querySelector('#delete-role-display-name').textContent = itemDisplayName; // Или #delete-role-name
            var form = modal.querySelector('#delete-user-role-form'); // Или #delete-role-form
            if (form) {
                form.action = '/admin/user-roles/' + itemId + '/delete';
            }
        });
    }
    // --- Конец логики для роли пользователя ---

    // --- Логика для модального окна удаления ПРАВА ПОЛЬЗОВАТЕЛЯ ---
    var deleteUserPermissionModal = document.getElementById('deleteUserPermissionModal');
    if (deleteUserPermissionModal) {
        deleteUserPermissionModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var itemId = button.getAttribute('data-permission-id');
            var itemDisplayName = button.getAttribute('data-permission-display-name'); // Или data-permission-name
            var modal = this;
            modal.querySelector('#delete-permission-display-name').textContent = itemDisplayName; // Или #delete-permission-name
            var form = modal.querySelector('#delete-user-permission-form'); // Или #delete-permission-form
            if (form) {
                form.action = '/admin/user-permissions/' + itemId + '/delete';
            }
        });
    }
    // --- Конец логики для права пользователя ---

});
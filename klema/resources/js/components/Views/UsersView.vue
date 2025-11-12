<template>
  <div class="users-view">
    <div class="users-header">
      <h2>
        <i class="fas fa-users"></i>
        User Management
      </h2>
      <button @click="showCreateModal = true" class="action-button primary">
        <i class="fas fa-plus"></i>
        Create User
      </button>
    </div>

    <!-- Search and Filter -->
    <div class="users-filters">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input 
          type="text" 
          v-model="searchQuery" 
          @input="debouncedSearch" 
          placeholder="Search users by name or email..."
          class="search-input"
        >
      </div>
      <div class="filter-group">
        <label>Role:</label>
        <select v-model="roleFilter" @change="fetchUsers" class="filter-select">
          <option value="">All Roles</option>
          <option value="admin">Admin</option>
          <option value="farmer">Farmer</option>
        </select>
      </div>
    </div>

    <!-- Users Table -->
    <div class="users-table-container">
      <table class="users-table" v-if="!loading && users.length > 0">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Email Verified</th>
            <th>Farms</th>
            <th>Activities</th>
            <th>Exports</th>
            <th>Created At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id" class="user-row">
            <td>{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td>
              <span class="role-badge" :class="user.role">{{ user.role }}</span>
            </td>
            <td>
              <i v-if="user.email_verified_at" class="fas fa-check-circle text-success"></i>
              <i v-else class="fas fa-times-circle text-danger"></i>
            </td>
            <td>{{ user.farms_count || 0 }}</td>
            <td>{{ user.activities_count || 0 }}</td>
            <td>{{ user.exports_count || 0 }}</td>
            <td>{{ formatDate(user.created_at) }}</td>
            <td>
              <div class="action-buttons">
                <button @click="viewUser(user)" class="btn-icon" title="View Details">
                  <i class="fas fa-eye"></i>
                </button>
                <button @click="editUser(user)" class="btn-icon" title="Edit User">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="resetPassword(user)" class="btn-icon" title="Reset Password">
                  <i class="fas fa-key"></i>
                </button>
                <button @click="manageSessions(user)" class="btn-icon" title="Manage Sessions">
                  <i class="fas fa-desktop"></i>
                </button>
                <button 
                  @click="deleteUser(user)" 
                  class="btn-icon danger" 
                  title="Delete User"
                  :disabled="user.id === currentUserId"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="loading" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading users...</p>
      </div>

      <div v-if="!loading && users.length === 0" class="empty-state">
        <i class="fas fa-users"></i>
        <p>No users found</p>
      </div>
    </div>

    <!-- Pagination -->
    <div class="pagination" v-if="pagination && pagination.last_page > 1">
      <button 
        @click="changePage(pagination.current_page - 1)" 
        :disabled="pagination.current_page === 1"
        class="page-button"
      >
        Previous
      </button>
      <span class="page-info">
        Page {{ pagination.current_page }} of {{ pagination.last_page }}
      </span>
      <button 
        @click="changePage(pagination.current_page + 1)" 
        :disabled="pagination.current_page === pagination.last_page"
        class="page-button"
      >
        Next
      </button>
    </div>

    <!-- Create/Edit User Modal -->
    <div v-if="showCreateModal || showEditModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ showCreateModal ? 'Create User' : 'Edit User' }}</h3>
          <button @click="closeModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <form @submit.prevent="saveUser" class="modal-body">
          <div class="form-group">
            <label>Name *</label>
            <input type="text" v-model="userForm.name" required class="form-control">
          </div>
          <div class="form-group">
            <label>Email *</label>
            <input type="email" v-model="userForm.email" required class="form-control">
          </div>
          <div class="form-group">
            <label>Role *</label>
            <select v-model="userForm.role" required class="form-control">
              <option value="farmer">Farmer</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="form-group" v-if="showCreateModal">
            <label>Password *</label>
            <input type="password" v-model="userForm.password" required class="form-control">
          </div>
          <div class="form-group" v-if="showCreateModal">
            <label>Confirm Password *</label>
            <input type="password" v-model="userForm.password_confirmation" required class="form-control">
          </div>
          <div class="form-group" v-if="showEditModal">
            <label>New Password (leave blank to keep current)</label>
            <input type="password" v-model="userForm.password" class="form-control">
          </div>
          <div class="form-group" v-if="showEditModal && userForm.password">
            <label>Confirm New Password</label>
            <input type="password" v-model="userForm.password_confirmation" class="form-control">
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeModal" class="action-button">Cancel</button>
            <button type="submit" :disabled="saving" class="action-button primary">
              <i class="fas fa-spinner fa-spin" v-if="saving"></i>
              <span v-else>{{ showCreateModal ? 'Create' : 'Update' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- View User Modal -->
    <div v-if="showViewModal && selectedUser" class="modal-overlay" @click="showViewModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>User Details</h3>
          <button @click="showViewModal = false" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="user-details">
            <div class="detail-row">
              <label>Name:</label>
              <span>{{ selectedUser.name }}</span>
            </div>
            <div class="detail-row">
              <label>Email:</label>
              <span>{{ selectedUser.email }}</span>
            </div>
            <div class="detail-row">
              <label>Role:</label>
              <span class="role-badge" :class="selectedUser.role">{{ selectedUser.role }}</span>
            </div>
            <div class="detail-row">
              <label>Email Verified:</label>
              <span v-if="selectedUser.email_verified_at">
                <i class="fas fa-check-circle text-success"></i> Yes
              </span>
              <span v-else>
                <i class="fas fa-times-circle text-danger"></i> No
              </span>
            </div>
            <div class="detail-row">
              <label>Farms:</label>
              <span>{{ selectedUser.farms_count || 0 }}</span>
            </div>
            <div class="detail-row">
              <label>Activities:</label>
              <span>{{ selectedUser.activities_count || 0 }}</span>
            </div>
            <div class="detail-row">
              <label>Exports:</label>
              <span>{{ selectedUser.exports_count || 0 }}</span>
            </div>
            <div class="detail-row" v-if="selectedUser.farms && selectedUser.farms.length > 0">
              <label>Farms List:</label>
              <ul class="farms-list">
                <li v-for="farm in selectedUser.farms" :key="farm.farm_id">
                  {{ farm.farm_name }} ({{ farm.latitude }}, {{ farm.longitude }})
                </li>
              </ul>
            </div>
            <div class="detail-row">
              <label>Created At:</label>
              <span>{{ formatDate(selectedUser.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sessions Modal -->
    <div v-if="showSessionsModal && selectedUser" class="modal-overlay" @click="showSessionsModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>User Sessions - {{ selectedUser.name }}</h3>
          <button @click="showSessionsModal = false" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="sessionsLoading" class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading sessions...</p>
          </div>
          <div v-else-if="sessions.length === 0" class="empty-state">
            <p>No active sessions</p>
          </div>
          <div v-else class="sessions-list">
            <div v-for="session in sessions" :key="session.id" class="session-item">
              <div class="session-info">
                <div class="session-ip">
                  <i class="fas fa-network-wired"></i>
                  {{ session.ip_address }}
                </div>
                <div class="session-user-agent">
                  {{ session.user_agent }}
                </div>
                <div class="session-last-activity">
                  Last activity: {{ session.last_activity }}
                </div>
              </div>
              <button @click="revokeSession(session.id)" class="action-button danger small">
                <i class="fas fa-trash"></i>
                Revoke
              </button>
            </div>
          </div>
          <div class="modal-actions">
            <button @click="revokeAllSessions" class="action-button danger">
              <i class="fas fa-ban"></i>
              Revoke All Sessions
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { authorizedFetch } from '../../services/auth';
import { useGlobalAlerts } from '../../composables/useGlobalAlerts';

const { showSuccess, showError } = useGlobalAlerts();

const users = ref([]);
const loading = ref(false);
const saving = ref(false);
const searchQuery = ref('');
const roleFilter = ref('');
const pagination = ref(null);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showViewModal = ref(false);
const showSessionsModal = ref(false);
const selectedUser = ref(null);
const sessions = ref([]);
const sessionsLoading = ref(false);

const currentUserId = ref(null);

const userForm = reactive({
  name: '',
  email: '',
  role: 'farmer',
  password: '',
  password_confirmation: '',
});

let searchTimeout = null;

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchUsers();
  }, 500);
};

onMounted(async () => {
  await fetchCurrentUser();
  await fetchUsers();
});

const fetchCurrentUser = async () => {
  try {
    const response = await authorizedFetch('/api/auth/me');
    if (response.ok) {
      const data = await response.json();
      currentUserId.value = data.user?.id;
    }
  } catch (error) {
    console.error('Failed to fetch current user:', error);
  }
};

const fetchUsers = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (searchQuery.value) {
      params.append('search', searchQuery.value);
    }
    if (roleFilter.value) {
      params.append('role', roleFilter.value);
    }
    
    const response = await authorizedFetch(`/api/admin/users?${params.toString()}`);
    if (!response.ok) {
      throw new Error('Failed to fetch users');
    }
    
    const data = await response.json();
    users.value = data.users || [];
    pagination.value = data.pagination || null;
  } catch (error) {
    console.error('Error fetching users:', error);
    showError('Error', 'Failed to load users. Please try again.');
  } finally {
    loading.value = false;
  }
};

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    fetchUsers();
  }
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  showViewModal.value = false;
  showSessionsModal.value = false;
  selectedUser.value = null;
  resetForm();
};

const resetForm = () => {
  userForm.name = '';
  userForm.email = '';
  userForm.role = 'farmer';
  userForm.password = '';
  userForm.password_confirmation = '';
};

const viewUser = async (user) => {
  try {
    const response = await authorizedFetch(`/api/admin/users/${user.id}`);
    if (!response.ok) {
      throw new Error('Failed to fetch user details');
    }
    const data = await response.json();
    selectedUser.value = data.user;
    showViewModal.value = true;
  } catch (error) {
    console.error('Error fetching user details:', error);
    showError('Error', 'Failed to load user details.');
  }
};

const editUser = (user) => {
  selectedUser.value = user;
  userForm.name = user.name;
  userForm.email = user.email;
  userForm.role = user.role;
  userForm.password = '';
  userForm.password_confirmation = '';
  showEditModal.value = true;
};

const saveUser = async () => {
  saving.value = true;
  try {
    const url = showCreateModal.value 
      ? '/api/admin/users'
      : `/api/admin/users/${selectedUser.value.id}`;
    
    const method = showCreateModal.value ? 'POST' : 'PUT';
    
    const payload = {
      name: userForm.name,
      email: userForm.email,
      role: userForm.role,
    };
    
    if (showCreateModal.value) {
      payload.password = userForm.password;
      payload.password_confirmation = userForm.password_confirmation;
    } else if (userForm.password) {
      payload.password = userForm.password;
      payload.password_confirmation = userForm.password_confirmation;
    }
    
    const response = await authorizedFetch(url, {
      method,
      body: payload,
    });
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Failed to save user');
    }
    
    showSuccess('Success', showCreateModal.value ? 'User created successfully!' : 'User updated successfully!');
    closeModal();
    await fetchUsers();
  } catch (error) {
    console.error('Error saving user:', error);
    showError('Error', error.message || 'Failed to save user. Please try again.');
  } finally {
    saving.value = false;
  }
};

const deleteUser = async (user) => {
  if (user.id === currentUserId.value) {
    showError('Error', 'You cannot delete your own account.');
    return;
  }
  
  if (!confirm(`Are you sure you want to delete user "${user.name}"? This action cannot be undone.`)) {
    return;
  }
  
  try {
    const response = await authorizedFetch(`/api/admin/users/${user.id}`, {
      method: 'DELETE',
    });
    
    if (!response.ok) {
      throw new Error('Failed to delete user');
    }
    
    showSuccess('Success', 'User deleted successfully!');
    await fetchUsers();
  } catch (error) {
    console.error('Error deleting user:', error);
    showError('Error', 'Failed to delete user. Please try again.');
  }
};

const resetPassword = async (user) => {
  const newPassword = prompt('Enter new password for user:');
  if (!newPassword) return;
  
  const confirmPassword = prompt('Confirm new password:');
  if (newPassword !== confirmPassword) {
    showError('Error', 'Passwords do not match.');
    return;
  }
  
  try {
    const response = await authorizedFetch(`/api/admin/users/${user.id}/reset-password`, {
      method: 'POST',
      body: {
        password: newPassword,
        password_confirmation: confirmPassword,
      },
    });
    
    if (!response.ok) {
      throw new Error('Failed to reset password');
    }
    
    showSuccess('Success', 'Password reset successfully. All user sessions have been terminated.');
  } catch (error) {
    console.error('Error resetting password:', error);
    showError('Error', 'Failed to reset password. Please try again.');
  }
};

const manageSessions = async (user) => {
  selectedUser.value = user;
  sessionsLoading.value = true;
  showSessionsModal.value = true;
  
  try {
    const response = await authorizedFetch(`/api/admin/users/${user.id}/sessions`);
    if (!response.ok) {
      throw new Error('Failed to fetch sessions');
    }
    
    const data = await response.json();
    sessions.value = data.sessions || [];
  } catch (error) {
    console.error('Error fetching sessions:', error);
    showError('Error', 'Failed to load user sessions.');
  } finally {
    sessionsLoading.value = false;
  }
};

const revokeSession = async (sessionId) => {
  try {
    const response = await authorizedFetch(`/api/admin/users/${selectedUser.value.id}/sessions`, {
      method: 'DELETE',
      body: { session_id: sessionId },
    });
    
    if (!response.ok) {
      throw new Error('Failed to revoke session');
    }
    
    showSuccess('Success', 'Session revoked successfully.');
    await manageSessions(selectedUser.value);
  } catch (error) {
    console.error('Error revoking session:', error);
    showError('Error', 'Failed to revoke session. Please try again.');
  }
};

const revokeAllSessions = async () => {
  if (!confirm('Are you sure you want to revoke all sessions for this user?')) {
    return;
  }
  
  try {
    const response = await authorizedFetch(`/api/admin/users/${selectedUser.value.id}/sessions`, {
      method: 'DELETE',
    });
    
    if (!response.ok) {
      throw new Error('Failed to revoke sessions');
    }
    
    showSuccess('Success', 'All sessions revoked successfully.');
    await manageSessions(selectedUser.value);
  } catch (error) {
    console.error('Error revoking sessions:', error);
    showError('Error', 'Failed to revoke sessions. Please try again.');
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
};
</script>

<style scoped>
.users-view {
  width: 100%;
  padding: 20px;
  overflow-y: auto;
  height: 100%;
}

.users-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.users-header h2 {
  margin: 0;
  font-size: 24px;
  color: #333;
}

.users-filters {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
}

.search-bar {
  flex: 1;
  position: relative;
}

.search-bar i {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
}

.search-input {
  width: 100%;
  padding: 10px 10px 10px 35px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 10px;
}

.filter-select {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.users-table-container {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.users-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table thead {
  background: #f5f5f5;
}

.users-table th {
  padding: 12px;
  text-align: left;
  font-weight: 600;
  color: #333;
  border-bottom: 2px solid #ddd;
}

.users-table td {
  padding: 12px;
  border-bottom: 1px solid #eee;
}

.user-row:hover {
  background: #f9f9f9;
}

.role-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.role-badge.admin {
  background: #dc3545;
  color: white;
}

.role-badge.farmer {
  background: #28a745;
  color: white;
}

.action-buttons {
  display: flex;
  gap: 5px;
}

.btn-icon {
  padding: 6px 10px;
  border: none;
  background: #f0f0f0;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-icon:hover:not(:disabled) {
  background: #e0e0e0;
}

.btn-icon.danger {
  background: #fee;
  color: #dc3545;
}

.btn-icon.danger:hover:not(:disabled) {
  background: #fcc;
}

.btn-icon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.text-success {
  color: #28a745;
}

.text-danger {
  color: #dc3545;
}

.loading-state,
.empty-state {
  padding: 40px;
  text-align: center;
  color: #999;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  margin-top: 20px;
}

.page-button {
  padding: 8px 16px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
}

.page-button:hover:not(:disabled) {
  background: #f5f5f5;
}

.page-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.modal-header h3 {
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: #999;
}

.modal-close:hover {
  color: #333;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 600;
  color: #333;
}

.form-control {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.action-button {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.2s;
}

.action-button.primary {
  background: #007bff;
  color: white;
}

.action-button.primary:hover {
  background: #0056b3;
}

.action-button.danger {
  background: #dc3545;
  color: white;
}

.action-button.danger:hover {
  background: #c82333;
}

.action-button.small {
  padding: 6px 12px;
  font-size: 12px;
}

.user-details {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 10px;
  background: #f9f9f9;
  border-radius: 4px;
}

.detail-row label {
  font-weight: 600;
  color: #666;
}

.farms-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.farms-list li {
  padding: 5px 0;
  border-bottom: 1px solid #eee;
}

.sessions-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.session-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: #f9f9f9;
  border-radius: 4px;
}

.session-info {
  flex: 1;
}

.session-ip {
  font-weight: 600;
  margin-bottom: 5px;
}

.session-user-agent {
  font-size: 12px;
  color: #666;
  margin-bottom: 5px;
}

.session-last-activity {
  font-size: 12px;
  color: #999;
}
</style>


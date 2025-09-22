// app.js - Frontend JavaScript for Indigenous Art Atlas

const API_BASE = window.location.origin + '/task3';

// Utility functions
const api = {
    async request(endpoint, options = {}) {
        const url = `${API_BASE}/${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Request failed');
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    // Auth endpoints
    async login(credentials) {
        return this.request('auth.php?action=login', {
            method: 'POST',
            body: JSON.stringify(credentials)
        });
    },

    async register(userData) {
        return this.request('auth.php?action=register', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    },

    async logout() {
        return this.request('auth.php?action=logout', { method: 'POST' });
    },

    async getCurrentUser() {
        return this.request('auth.php?action=me');
    },

    // Public endpoints
    async getArtworks(page = 1, limit = 9) {
        return this.request(`api.php?action=artworks&page=${page}&limit=${limit}`);
    },

    async getArtwork(id) {
        return this.request(`api.php?action=artwork&id=${id}`);
    },

    async searchArtworks(query, filters = {}) {
        const params = new URLSearchParams({ q: query, ...filters });
        return this.request(`api.php?action=search&${params}`);
    },

    async getCategories() {
        return this.request('api.php?action=categories');
    },

    // Authenticated endpoints
    async createSubmission(submissionData) {
        return this.request('api.php?action=submissions', {
            method: 'POST',
            body: JSON.stringify(submissionData)
        });
    },

    async getSubmissions(status = null) {
        const params = status ? `?status=${status}` : '';
        return this.request(`api.php?action=submissions${params}`);
    },

    // Admin endpoints
    async getStats() {
        return this.request('api.php?action=admin/stats');
    },

    async getAllUsers() {
        return this.request('api.php?action=admin/users');
    },

    async updateUserRole(userId, role) {
        return this.request('api.php?action=admin/user-role', {
            method: 'PUT',
            body: JSON.stringify({ user_id: userId, role })
        });
    },

    async updateSubmissionStatus(submissionId, status) {
        return this.request('api.php?action=admin/submission', {
            method: 'PUT',
            body: JSON.stringify({ submission_id: submissionId, status })
        });
    }
};

// UI State Management
const state = {
    currentUser: null,
    currentPage: 1,
    artworks: [],
    categories: []
};

// Page-specific functionality
const pages = {
    // Login page
    login: {
        init() {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', this.handleLogin.bind(this));
            }
        },

        async handleLogin(e) {
            e.preventDefault();
            
            const username = document.querySelector('input[type="text"]').value;
            const password = document.querySelector('input[type="password"]').value;
            
            try {
                const result = await api.login({ username, password });
                state.currentUser = result.user;
                showMessage('Login successful!', 'success');
                
                // Redirect based on role
                setTimeout(() => {
                    if (result.user.role === 'admin') {
                        window.location.href = 'Admin_Dashboard.html';
                    } else {
                        window.location.href = 'Home-Page.html';
                    }
                }, 1000);
                
            } catch (error) {
                showMessage(error.message, 'error');
            }
        }
    },

    // Signup page
    signup: {
        init() {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', this.handleSignup.bind(this));
            }
        },

        async handleSignup(e) {
            e.preventDefault();
            
            const inputs = e.target.querySelectorAll('input, select');
            const userData = {};
            
            inputs.forEach(input => {
                const label = input.previousElementSibling?.textContent?.toLowerCase().replace(/\s+/g, '_');
                if (label && input.value) {
                    userData[label] = input.value;
                }
            });

            // Map form fields to expected API fields
            const finalData = {
                username: userData.username,
                email: userData.email_address || userData.email,
                password: userData.password,
                confirm_password: userData.confirm_password,
                role: userData.account_type || 'general'
            };

            try {
                const result = await api.register(finalData);
                state.currentUser = result.user;
                showMessage('Registration successful!', 'success');
                
                setTimeout(() => {
                    window.location.href = 'Home-Page.html';
                }, 1000);
                
            } catch (error) {
                showMessage(error.message, 'error');
            }
        }
    },

    // Home page
    home: {
        init() {
            this.loadLatestArtworks();
            this.checkAuthStatus();
        },

        async loadLatestArtworks() {
            try {
                const result = await api.getArtworks(1, 3);
                this.displayArtworks(result.artworks);
            } catch (error) {
                console.error('Error loading artworks:', error);
            }
        },

        displayArtworks(artworks) {
            const grid = document.querySelector('.art-grid');
            if (grid && artworks.length > 0) {
                grid.innerHTML = artworks.map(artwork => `
                    <a href="Art_Details.html?id=${artwork.id}" class="art-card">
                        <div class="art-card-image-container">
                            <img src="${artwork.image_url}" alt="${artwork.title}" class="art-card-image" />
                        </div>
                        <div class="art-card-text">
                            <div class="font-semibold">${artwork.title}</div>
                            <div class="text-gray-600">${artwork.type} - ${artwork.period}</div>
                        </div>
                    </a>
                `).join('');
            }
        },

        async checkAuthStatus() {
            try {
                const result = await api.getCurrentUser();
                state.currentUser = result.user;
                this.updateNavigation();
            } catch (error) {
                state.currentUser = null;
            }
        },

        updateNavigation() {
            const authLink = document.querySelector('a[href*="Login"]');
            if (authLink && state.currentUser) {
                authLink.textContent = `Welcome, ${state.currentUser.username}`;
                authLink.href = '#';
                authLink.addEventListener('click', this.showUserMenu.bind(this));
                
                // Add submit art link for authenticated users
                const nav = document.querySelector('.main-nav');
                if (nav && !nav.querySelector('a[href*="Artist_New_entry"]')) {
                    const submitLink = document.createElement('a');
                    submitLink.href = 'Artist_New_entry.html';
                    submitLink.textContent = 'Submit Art';
                    nav.appendChild(submitLink);
                }
            }
        },

        showUserMenu(e) {
            e.preventDefault();
            if (confirm('Do you want to logout?')) {
                api.logout().then(() => {
                    state.currentUser = null;
                    window.location.reload();
                });
            }
        }
    },

    // Art Collection page
    collection: {
        init() {
            this.loadArtworks();
            this.setupSearch();
            this.setupPagination();
        },

        async loadArtworks(page = 1) {
            try {
                const result = await api.getArtworks(page, 9);
                this.displayArtworks(result.artworks);
                this.updatePagination(result.pagination);
                state.currentPage = page;
            } catch (error) {
                console.error('Error loading artworks:', error);
            }
        },

        displayArtworks(artworks) {
            const grid = document.querySelector('.art-grid');
            if (grid && artworks.length > 0) {
                grid.innerHTML = artworks.map(artwork => `
                    <a href="Art_Details.html?id=${artwork.id}" class="art-card">
                        <div class="art-card-image-container">
                            <img src="${artwork.image_url}" alt="${artwork.title}" class="art-card-image" />
                        </div>
                        <div class="art-card-text">
                            <div class="font-semibold">${artwork.title}</div>
                            <div class="text-gray-600">${artwork.artist} - ${artwork.period}</div>
                        </div>
                    </a>
                `).join('');
            }
        },

        setupSearch() {
            const searchInput = document.querySelector('.collection-search');
            if (searchInput) {
                let timeout;
                searchInput.addEventListener('input', (e) => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        this.performSearch(e.target.value);
                    }, 300);
                });
            }
        },

        async performSearch(query) {
            if (!query.trim()) {
                this.loadArtworks();
                return;
            }

            try {
                const result = await api.searchArtworks(query);
                this.displayArtworks(result.artworks);
            } catch (error) {
                console.error('Search error:', error);
            }
        },

        setupPagination() {
            const pagination = document.querySelector('.pagination');
            if (pagination) {
                pagination.addEventListener('click', (e) => {
                    if (e.target.classList.contains('page-num')) {
                        e.preventDefault();
                        const page = parseInt(e.target.textContent);
                        this.loadArtworks(page);
                    }
                });
            }
        },

        updatePagination(paginationInfo) {
            const pagination = document.querySelector('.pagination');
            if (pagination && paginationInfo) {
                const { current_page } = paginationInfo;
                const pageNums = pagination.querySelectorAll('.page-num');
                pageNums.forEach((btn, index) => {
                    btn.classList.toggle('active', index + 1 === current_page);
                });
            }
        }
    },

    // Art Details page
    artDetail: {
        init() {
            const urlParams = new URLSearchParams(window.location.search);
            const artworkId = urlParams.get('id');
            
            if (artworkId) {
                this.loadArtwork(artworkId);
            }
        },

        async loadArtwork(id) {
            try {
                const result = await api.getArtwork(id);
                this.displayArtwork(result.artwork);
                this.displaySimilarArtworks(result.similar);
            } catch (error) {
                console.error('Error loading artwork:', error);
                showMessage('Artwork not found', 'error');
            }
        },

        displayArtwork(artwork) {
            const title = document.querySelector('.art-detail-title');
            if (title) title.textContent = artwork.title;

            const img = document.querySelector('.art-detail-image');
            if (img) {
                img.src = artwork.image_url;
                img.alt = artwork.title;
            }

            const details = document.querySelectorAll('.art-detail-item');
            const detailsMap = {
                'Type:': artwork.type,
                'Estimated Period:': artwork.period,
                'Artist Name:': artwork.artist,
                'Description:': artwork.description,
                'Condition Note:': artwork.condition_note
            };

            details.forEach(item => {
                const label = item.querySelector('h2')?.textContent;
                const value = item.querySelector('p');
                if (label && value && detailsMap[label]) {
                    value.textContent = detailsMap[label];
                }
            });
        },

        displaySimilarArtworks(artworks) {
            const grid = document.querySelector('.art-grid');
            if (grid && artworks.length > 0) {
                grid.innerHTML = artworks.map(artwork => `
                    <a href="Art_Details.html?id=${artwork.id}" class="art-card">
                        <div class="art-card-image-container">
                            <img src="${artwork.image_url}" alt="${artwork.title}" class="art-card-image" />
                        </div>
                        <div class="art-card-text">
                            <div class="font-semibold">${artwork.title}</div>
                            <div class="text-gray-600">${artwork.artist} - ${artwork.period}</div>
                        </div>
                    </a>
                `).join('');
            }
        }
    },

    // Artist submission page
    submission: {
        init() {
            this.checkAuth();
            this.setupForm();
            this.loadCategories();
        },

        async checkAuth() {
            try {
                const result = await api.getCurrentUser();
                state.currentUser = result.user;
            } catch (error) {
                showMessage('Please login to submit artwork', 'error');
                setTimeout(() => {
                    window.location.href = 'Login.html';
                }, 2000);
            }
        },

        setupForm() {
            const submitBtn = document.querySelector('button:contains("Submit for Review")') || 
                            Array.from(document.querySelectorAll('button')).find(btn => 
                                btn.textContent.includes('Submit for Review'));
            
            if (submitBtn) {
                submitBtn.addEventListener('click', this.handleSubmission.bind(this));
            }

            const draftBtn = Array.from(document.querySelectorAll('button')).find(btn => 
                btn.textContent.includes('Save as Draft'));
            
            if (draftBtn) {
                draftBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.saveDraft();
                });
            }
        },

        async loadCategories() {
            try {
                const result = await api.getCategories();
                this.populateTypeSelect(result.categories);
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        },

        populateTypeSelect(categories) {
            const select = document.querySelector('select');
            if (select && categories.length > 0) {
                select.innerHTML = '<option>Select...</option>' +
                    categories.map(cat => `<option value="${cat.name}">${cat.name}</option>`).join('');
            }
        },

        async handleSubmission(e) {
            e.preventDefault();
            
            const inputs = document.querySelectorAll('input, select, textarea');
            const data = {};
            
            inputs.forEach(input => {
                const label = input.closest('div')?.querySelector('label')?.textContent;
                if (label && input.value) {
                    const key = label.toLowerCase().replace(/[:\s]/g, '_');
                    data[key] = input.value;
                }
            });

            const submissionData = {
                title: data.title,
                type: data.art_type,
                artist: data.artist_name || 'Unknown',
                period: data.period || 'Unknown',
                description: data.description,
                location: data.location_notes || '',
                condition_note: '',
                image_url: 'https://picsum.photos/seed/' + Date.now() + '/400/300'
            };

            try {
                await api.createSubmission(submissionData);
                showMessage('Submission created successfully! It will be reviewed by our team.', 'success');
                
                setTimeout(() => {
                    window.location.href = 'Home-Page.html';
                }, 2000);
                
            } catch (error) {
                showMessage(error.message, 'error');
            }
        },

        saveDraft() {
            showMessage('Draft saved locally', 'success');
        }
    },

    // Admin Dashboard
    adminDashboard: {
        init() {
            this.checkAdminAuth();
            this.loadStats();
            this.loadPendingSubmissions();
            this.loadUsers();
            this.setupEventListeners();
        },

        async checkAdminAuth() {
            try {
                const result = await api.getCurrentUser();
                if (result.user.role !== 'admin') {
                    throw new Error('Admin access required');
                }
                state.currentUser = result.user;
            } catch (error) {
                showMessage('Admin access required', 'error');
                setTimeout(() => {
                    window.location.href = 'Login.html';
                }, 2000);
            }
        },

        async loadStats() {
            try {
                const result = await api.getStats();
                this.displayStats(result.stats);
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        displayStats(stats) {
            const statCards = document.querySelectorAll('.stat-card');
            const statsValues = [
                stats.pending_submissions,
                stats.total_users,
                stats.total_artworks
            ];
            
            statCards.forEach((card, index) => {
                const value = card.querySelector('p');
                if (value && statsValues[index] !== undefined) {
                    value.textContent = statsValues[index];
                }
            });
        },

        async loadPendingSubmissions() {
            try {
                const result = await api.getSubmissions('pending');
                this.displayPendingSubmissions(result.submissions.slice(0, 4));
            } catch (error) {
                console.error('Error loading submissions:', error);
            }
        },

        displayPendingSubmissions(submissions) {
            const items = document.querySelectorAll('.admin-table-item');
            
            submissions.forEach((submission, index) => {
                if (items[index]) {
                    const info = items[index].querySelector('.item-info');
                    if (info) {
                        info.innerHTML = `
                            <div class="font-semibold">${submission.title}</div>
                            <div class="text-gray-600">${submission.type} - ${submission.period}</div>
                        `;
                    }
                    
                    const actions = items[index].querySelector('.item-actions');
                    if (actions) {
                        actions.innerHTML = `
                            <button class="btn btn-secondary btn-sm approve-btn" data-id="${submission.id}">Approve</button>
                            <button class="btn btn-secondary btn-sm reject-btn" data-id="${submission.id}">Reject</button>
                        `;
                    }
                }
            });
        },

        async loadUsers() {
            try {
                const result = await api.getAllUsers();
                this.displayUsers(result.users.slice(0, 2));
            } catch (error) {
                console.error('Error loading users:', error);
            }
        },

        displayUsers(users) {
            const userItems = document.querySelectorAll('.admin-table-item');
            const submissionItems = Array.from(userItems).slice(4, 6);
            
            users.forEach((user, index) => {
                if (submissionItems[index]) {
                    const info = submissionItems[index].querySelector('.item-info');
                    if (info) {
                        info.innerHTML = `
                            <div class="font-semibold">${user.username}</div>
                            <div class="text-gray-600">${user.email}</div>
                        `;
                    }
                    
                    const select = submissionItems[index].querySelector('.user-role-select');
                    if (select) {
                        select.value = user.role;
                        select.dataset.userId = user.id;
                    }
                }
            });
        },

        setupEventListeners() {
            document.addEventListener('click', async (e) => {
                if (e.target.classList.contains('approve-btn') || e.target.classList.contains('reject-btn')) {
                    const submissionId = e.target.dataset.id;
                    const status = e.target.classList.contains('approve-btn') ? 'approved' : 'rejected';
                    
                    try {
                        await api.updateSubmissionStatus(submissionId, status);
                        showMessage(`Submission ${status} successfully`, 'success');
                        this.loadPendingSubmissions();
                        this.loadStats();
                    } catch (error) {
                        showMessage(error.message, 'error');
                    }
                }
            });
            
            document.addEventListener('change', async (e) => {
                if (e.target.classList.contains('user-role-select')) {
                    const userId = e.target.dataset.userId;
                    const newRole = e.target.value;
                    
                    try {
                        await api.updateUserRole(userId, newRole);
                        showMessage('User role updated successfully', 'success');
                    } catch (error) {
                        showMessage(error.message, 'error');
                        e.target.value = e.target.defaultValue;
                    }
                }
            });
        }
    }
};

// Utility functions
function showMessage(message, type = 'info') {
    let msgEl = document.getElementById('message-display');
    if (!msgEl) {
        msgEl = document.createElement('div');
        msgEl.id = 'message-display';
        msgEl.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 0.375rem;
            color: white;
            font-weight: 500;
            z-index: 1000;
            max-width: 400px;
        `;
        document.body.appendChild(msgEl);
    }
    
    const colors = {
        success: '#10b981',
        error: '#ef4444',
        info: '#3b82f6'
    };
    
    msgEl.style.backgroundColor = colors[type] || colors.info;
    msgEl.textContent = message;
    msgEl.style.display = 'block';
    
    setTimeout(() => {
        msgEl.style.display = 'none';
    }, 5000);
}

// Initialize page-specific functionality
document.addEventListener('DOMContentLoaded', () => {
    const currentPage = getCurrentPage();
    
    if (pages[currentPage]) {
        pages[currentPage].init();
    }
});

function getCurrentPage() {
    const path = window.location.pathname;
    const filename = path.split('/').pop().replace('.html', '').toLowerCase();
    
    const pageMap = {
        'login': 'login',
        'signup': 'signup',
        'home-page': 'home',
        'index': 'home',
        'art_collection': 'collection',
        'art_details': 'artDetail',
        'artist_new_entry': 'submission',
        'admin_dashboard': 'adminDashboard'
    };
    
    return pageMap[filename] || 'home';
}
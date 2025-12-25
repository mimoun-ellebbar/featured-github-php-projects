export default class RepoModelView {
    currentRepoId = null;
    _ = null;
    modalTitle = null;
    modalDescription = null;
    modalCreated = null;
    modalLastPush = null;
    modalUpdated = null;
    modalUrl = null;
    modalGithubLink = null;
    constructor() {
        // Cache all modal elements
        this._ = document.querySelector('#repoModal');
        this.modalTitle = document.querySelector('#modalTitle');
        this.modalDescription = document.querySelector('#modalDescription');
        this.modalCreated = document.querySelector('#modalCreated');
        this.modalLastPush = document.querySelector('#modalLastPush');
        this.modalUpdated = document.querySelector('#modalUpdated');
        this.modalUrl = document.querySelector('#modalUrl');
        this.modalGithubLink = document.querySelector('#modalGithubLink');

        // Setup event listeners
        this.setupEventListeners();

        console.log('ModelView initialized successfully');
    }

    setupEventListeners(){
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this._ && !this._.classList.contains('hidden')) {
                this.closeModal();
            }
        });

        // Close modal when clicking outside
        if (this._) {
            this._.addEventListener('click', (event) => {
                if (event.target === this._) {
                    this.closeModal();
                }
            });
        }
    }
    describe(){
        console.log(`Showing Repo ID : ${this.currentRepoId || undefined}`)
    }


    openModal(repoId) {
        this.currentRepoId = repoId;
        console.log('Opening modal for repository ID:', repoId);

        if (!this._) {
            console.error('Modal element not found!');
            return;
        }

        // Show loading state
        this.modalTitle.textContent = 'Loading...';
        this.modalDescription.textContent = 'Fetching repository details...';
        this._.classList.remove('hidden');

        // Fetch repository details
        fetch(`/github/repository/${repoId}`, {
            headers: {
                'Accept': 'application/json',
            }
        })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Repository data received:', data);
                this.populateModal(data);
            })
            .catch(error => {
                console.error('Error fetching repository:', error);
                this.modalTitle.textContent = 'Error';
                this.modalDescription.textContent = `Failed to load repository details: ${error.message}`;
            });
    }

    populateModal(data){
        this.modalTitle.textContent = data.name || 'Unknown Repository';
        this.modalDescription.textContent = data.description || 'No description available';

        // Format and update dates
        this.modalCreated.textContent = this.formatDate(data.created_at);
        this.modalLastPush.textContent = this.formatDate(data.last_pushed_at);
        this.modalUpdated.textContent = this.formatDate(data.updated_at);

        // Update URL
        this.modalUrl.textContent = data.url || 'N/A';
        this.modalGithubLink.href = data.url || '#';
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: 'numeric',
                second: 'numeric'
            });
        } catch (error) {
            console.error('Error formatting date:', error);
            return 'Invalid date';
        }
    }

    closeModal() {
        if (this._) {
            this._.classList.add('hidden');
            console.log(`Model closed for Repo #${this.currentRepoId}`);
        }
    }


}

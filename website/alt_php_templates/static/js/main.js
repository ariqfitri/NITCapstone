// KidsSmart main JavaScript file

document.addEventListener('DOMContentLoaded', function() {
    // Modal functionality
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');
    const loginBtn = document.getElementById('loginBtn');
    const registerBtn = document.getElementById('registerBtn');
    const loginReviewBtn = document.getElementById('loginReviewBtn');
    const closeBtns = document.getElementsByClassName('close');
    const switchToRegister = document.getElementById('switchToRegister');
    const switchToLogin = document.getElementById('switchToLogin');

    if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            loginModal.style.display = 'block';
        });
    }

    if (registerBtn) {
        registerBtn.addEventListener('click', function(e) {
            e.preventDefault();
            registerModal.style.display = 'block';
        });
    }

    if (loginReviewBtn) {
        loginReviewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            loginModal.style.display = 'block';
        });
    }

    for (let i = 0; i < closeBtns.length; i++) {
        closeBtns[i].addEventListener('click', function() {
            loginModal.style.display = 'none';
            registerModal.style.display = 'none';
        });
    }

    if (switchToRegister) {
        switchToRegister.addEventListener('click', function(e) {
            e.preventDefault();
            loginModal.style.display = 'none';
            registerModal.style.display = 'block';
        });
    }

    if (switchToLogin) {
        switchToLogin.addEventListener('click', function(e) {
            e.preventDefault();
            registerModal.style.display = 'none';
            loginModal.style.display = 'block';
        });
    }

    window.addEventListener('click', function(event) {
        if (event.target == loginModal) {
            loginModal.style.display = 'none';
        } else if (event.target == registerModal) {
            registerModal.style.display = 'none';
        }
    });

    // Login Form Submission
    const loginForm = document.querySelector('#loginModal form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            
            // Use WordPress AJAX for login
            const data = new FormData();
            data.append('action', 'kidssmart_login');
            data.append('email', email);
            data.append('password', password);
            
            fetch(kidssmart_params.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }

    // Register Form Submission
    const registerForm = document.querySelector('#registerModal form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const firstName = document.getElementById('registerFirstName').value;
            const lastName = document.getElementById('registerLastName').value;
            const email = document.getElementById('registerEmail').value;
            const password = document.getElementById('registerPassword').value;
            const suburb = document.getElementById('registerSuburb').value;
            
            // Use WordPress AJAX for registration
            const data = new FormData();
            data.append('action', 'kidssmart_register');
            data.append('first_name', firstName);
            data.append('last_name', lastName);
            data.append('email', email);
            data.append('password', password);
            data.append('suburb', suburb);
            
            fetch(kidssmart_params.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Registration successful. Please log in.');
                    registerModal.style.display = 'none';
                    loginModal.style.display = 'block';
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }

    // Program Search Form
    const searchForm = document.getElementById('program-search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const location = document.getElementById('location').value;
            const category = document.getElementById('category').value;
            const ageGroup = document.getElementById('ageGroup').value;
            
            let url = kidssmart_params.programs_url;
            const params = [];
            
            if (location) params.push('location=' + location);
            if (category) params.push('category=' + category);
            if (ageGroup) params.push('age_group=' + ageGroup);
            
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            
            window.location.href = url;
        });
    }

    // Program Filter Form
    const filterForm = document.getElementById('program-filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const location = document.getElementById('location-filter').value;
            const category = document.getElementById('category-filter').value;
            const ageGroup = document.getElementById('age-filter').value;
            
            let url = window.location.pathname;
            const params = [];
            
            if (location) params.push('location=' + location);
            if (category) params.push('category=' + category);
            if (ageGroup) params.push('age_group=' + ageGroup);
            
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            
            window.location.href = url;
        });
    }

    // Review Form
    const reviewForm = document.getElementById('program-review-form');
    if (reviewForm) {
        const reviewText = document.getElementById('review-text');
        const wordCount = document.getElementById('word-count');
        
        reviewText.addEventListener('input', function() {
            wordCount.textContent = this.value.length;
        });
        
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const rating = document.querySelector('input[name="rating"]:checked');
            if (!rating) {
                alert('Please select a rating.');
                return;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'kidssmart_submit_review');
            
            fetch(kidssmart_params.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Your review has been submitted.');
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }
});
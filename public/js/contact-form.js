// Contact Form Handler
document.addEventListener('DOMContentLoaded', function() {
    // Find contact form
    const contactForm = document.querySelector('#contactForm, .contact-form, form[action*="contact"]');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(contactForm);
            const submitButton = contactForm.querySelector('button[type="submit"], input[type="submit"]');
            const originalButtonText = submitButton.textContent;
            
            // Show loading state
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';
            
            // Clear previous messages
            const existingMessages = contactForm.querySelectorAll('.alert, .form-message');
            existingMessages.forEach(msg => msg.remove());
            
            // Send AJAX request
            fetch('/api/contact/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    name: formData.get('name') || formData.get('contact_name'),
                    email: formData.get('email') || formData.get('contact_email'),
                    subject: formData.get('subject') || formData.get('contact_subject'),
                    message: formData.get('message') || formData.get('contact_message')
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showMessage(contactForm, 'success', data.message);
                    // Reset form
                    contactForm.reset();
                } else {
                    // Show error message
                    if (data.errors) {
                        // Show validation errors
                        Object.keys(data.errors).forEach(field => {
                            const input = contactForm.querySelector(`[name="${field}"], [name="contact_${field}"]`);
                            if (input) {
                                showFieldError(input, data.errors[field][0]);
                            }
                        });
                        showMessage(contactForm, 'error', 'Please fix the errors above.');
                    } else {
                        showMessage(contactForm, 'error', data.message || 'Failed to send message.');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage(contactForm, 'error', 'Network error. Please try again.');
            })
            .finally(() => {
                // Reset button
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            });
        });
    }
    
    // Helper function to show messages
    function showMessage(form, type, message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} form-message`;
        messageDiv.style.marginTop = '15px';
        messageDiv.textContent = message;
        
        // Insert after form or before submit button
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitButton) {
            submitButton.parentNode.insertBefore(messageDiv, submitButton);
        } else {
            form.appendChild(messageDiv);
        }
        
        // Auto-hide after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }
    }
    
    // Helper function to show field errors
    function showFieldError(input, message) {
        // Remove existing error
        const existingError = input.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Add error styling
        input.classList.add('is-invalid');
        
        // Create error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error text-danger';
        errorDiv.style.fontSize = '14px';
        errorDiv.style.marginTop = '5px';
        errorDiv.textContent = message;
        
        input.parentNode.appendChild(errorDiv);
        
        // Remove error when user starts typing
        input.addEventListener('input', function() {
            input.classList.remove('is-invalid');
            errorDiv.remove();
        });
    }
});

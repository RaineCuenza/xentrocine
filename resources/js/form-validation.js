/**
 * Form Validation Module
 * Provides real-time form validation with visual feedback
 */

// Validation rules
const validationRules = {
  email: {
    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    message: 'Please enter a valid email address'
  },
  phone: {
    pattern: /^[0-9]{10,11}$/,
    message: 'Please enter a valid phone number (10-11 digits)'
  },
  required: {
    test: (value) => value && value.trim().length > 0,
    message: 'This field is required'
  },
  minLength: (length) => ({
    test: (value) => value && value.length >= length,
    message: `Minimum ${length} characters required`
  }),
  maxLength: (length) => ({
    test: (value) => !value || value.length <= length,
    message: `Maximum ${length} characters allowed`
  }),
  min: (num) => ({
    test: (value) => !value || parseFloat(value) >= num,
    message: `Minimum value is ${num}`
  }),
  max: (num) => ({
    test: (value) => !value || parseFloat(value) <= num,
    message: `Maximum value is ${num}`
  }),
  match: (fieldId, label) => ({
    test: (value, form) => {
      const matchField = form.querySelector(`#${fieldId}`);
      return !value || !matchField || value === matchField.value;
    },
    message: `Must match ${label}`
  })
};

// Show validation error
function showError(input, message) {
  const formGroup = input.closest('.form-group, .admin-field, label');
  if (!formGroup) return;
  
  // Remove existing error
  const existingError = formGroup.querySelector('.validation-error');
  if (existingError) existingError.remove();
  
  // Add error class
  input.classList.add('input--error');
  input.setAttribute('aria-invalid', 'true');
  
  // Create error message
  const errorEl = document.createElement('div');
  errorEl.className = 'validation-error';
  errorEl.textContent = message;
  errorEl.setAttribute('role', 'alert');
  
  // Insert after input
  input.parentNode.insertBefore(errorEl, input.nextSibling);
}

// Clear validation error
function clearError(input) {
  const formGroup = input.closest('.form-group, .admin-field, label');
  if (!formGroup) return;
  
  const existingError = formGroup.querySelector('.validation-error');
  if (existingError) existingError.remove();
  
  input.classList.remove('input--error');
  input.removeAttribute('aria-invalid');
}

// Validate single field
function validateField(input, rules = []) {
  const value = input.value;
  const form = input.closest('form');
  
  // Check if field is required
  const isRequired = input.hasAttribute('required') || rules.includes('required');
  
  // If empty and not required, clear error and return true
  if (!value.trim() && !isRequired) {
    clearError(input);
    return true;
  }
  
  // Check each rule
  for (const rule of rules) {
    let validator, message;
    
    if (typeof rule === 'string') {
      // Built-in rule
      if (validationRules[rule]) {
        if (rule === 'required') {
          validator = validationRules.required.test;
          message = validationRules.required.message;
        } else {
          validator = (val) => validationRules[rule].pattern.test(val);
          message = validationRules[rule].message;
        }
      }
    } else if (typeof rule === 'object') {
      // Custom rule object
      validator = rule.test;
      message = rule.message;
    }
    
    if (validator) {
      const isValid = typeof validator === 'function' 
        ? validator(value, form) 
        : validator.test(value);
      
      if (!isValid) {
        showError(input, message);
        return false;
      }
    }
  }
  
  clearError(input);
  return true;
}

// Setup validation for a form
export function setupFormValidation(formSelector, fieldRules = {}) {
  const form = document.querySelector(formSelector);
  if (!form) return;
  
  // Add validation to each field
  Object.keys(fieldRules).forEach(fieldSelector => {
    const field = form.querySelector(fieldSelector);
    if (!field) return;
    
    const rules = fieldRules[fieldSelector];
    
    // Validate on blur
    field.addEventListener('blur', () => {
      validateField(field, rules);
    });
    
    // Clear error on input (after initial validation)
    field.addEventListener('input', () => {
      if (field.classList.contains('input--error')) {
        validateField(field, rules);
      }
    });
  });
  
  // Validate on submit
  form.addEventListener('submit', (e) => {
    let isValid = true;
    
    Object.keys(fieldRules).forEach(fieldSelector => {
      const field = form.querySelector(fieldSelector);
      if (!field) return;
      
      const rules = fieldRules[fieldSelector];
      if (!validateField(field, rules)) {
        isValid = false;
      }
    });
    
    if (!isValid) {
      e.preventDefault();
      
      // Focus first invalid field
      const firstError = form.querySelector('.input--error');
      if (firstError) {
        firstError.focus();
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }
  });
}

// Quick validation helper for simple cases
export function validateInput(input, rules) {
  return validateField(input, rules);
}

// Export rules for custom usage
export { validationRules };

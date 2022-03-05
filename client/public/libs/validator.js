/* eslint-disable consistent-return */
/* eslint-disable no-unused-vars */
/* eslint-disable no-unused-expressions */
/* eslint-disable no-new */
/* eslint-disable no-param-reassign */
/* eslint-disable max-len */

const KB = 1024;
const MB = 1048576;
const GB = 1073741824;
const TB = 1099511627776;

const compValidator = {
  validateForm(form) {
    console.log('validating form');
    let valid;
    const errors = form.querySelectorAll('.error');
    let required = form.querySelectorAll('[required]');
    required = [...required];

    console.log(errors);
    if (errors.length !== 0 || required.find((field) => (!field.files && field.value === ''))) {
      valid = false;
      return valid;
    }

    valid = true;
    return valid;
  }, // END validateForm

  prepareElementProps(element) {
    const elementProps = {};
    elementProps.typeToValidate = element.getAttribute('data-validate');
    elementProps.errorID = `${element.id}-message`;
    elementProps.elementMessage = document.querySelector(`#${elementProps.errorID}`);
    elementProps.elementLength = elementProps.typeToValidate === 'file' && element.files[0] ? element.files[0].size : element.value.length;
    elementProps.elementValue = element.value;
    elementProps.min = element.getAttribute('data-min');
    elementProps.max = element.getAttribute('data-max');
    elementProps.allowed = element.getAttribute('data-allowed');
    elementProps.errorMessage = element.getAttribute('data-error');
    elementProps.helpMessage = element.getAttribute('data-help');
    return elementProps;
  }, // END prepareElement

  // prepareValidation
  prepareValidation(elementProps) {
    if (elementProps.typeToValidate === 'string') {
      if (elementProps.allowed && elementProps.allowed === '.,- ') return /^[a-zA-Z æøåÆØÅé'.:;,-]*$/;
      if (elementProps.allowed && elementProps.allowed === '- ') return /^[a-zA-Z æøåÆØÅé-]*$/;
      if (elementProps.allowed && elementProps.allowed === 'n.,- ') return /^[0-9a-zA-Z æøåÆØÅé(\r\n|\r|\n),',.":&%#,;-]*$/;
    }
    if (elementProps.typeToValidate === 'file') return /^([A-Za-z]:)(\\[A-Za-z_\-\s0-9\\.]+)+$/;
    if (elementProps.typeToValidate === 'url') return 'url';
    if (elementProps.typeToValidate === 'repeat-password') return document.getElementById('password').value;
    if (elementProps.typeToValidate === 'number') return /^[0-9]*$/;
    if (elementProps.typeToValidate === 'telephone') return /^[+]{1,1}[0-9+]*$/;
    if (elementProps.typeToValidate === 'email') return /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    if (elementProps.typeToValidate === 'select') return /^[0-9]{1,1}$/;
    if (elementProps.typeToValidate === 'checkbox') return /^[0-9a-zA-Z .(),/-æøåÆØÅ]*$/;

    return false;
  }, // END prepareValidation

  validateElement(element) {
    console.log('validating element');
    if (!element.required && element.value.length === 0) {
      element.classList.remove('error');
      return;
    }

    const elementProps = this.prepareElementProps(element);
    const validation = this.prepareValidation(elementProps);

    console.log(element);
    console.log(elementProps);

    if (!validation || (element.files && !element.files[0])) {
      element.classList.add('error');
      this.addError(element);
      this.addErrorProps(elementProps);
      return;
    }

    if (elementProps && validation) {
      if ((elementProps.elementLength < elementProps.min || elementProps.elementLength > elementProps.max)) {
        if (elementProps.elementLength < elementProps.min && elementProps.typeToValidate === 'file') { elementProps.errorMessage = 'The file is too small. Min. size is 10 KB'; }
        if (elementProps.elementLength > elementProps.max && elementProps.typeToValidate === 'file') { elementProps.errorMessage = 'The file is too large. Max. size is 10 MB'; }
        this.addError(element);
        this.addErrorProps(elementProps);
        return;
      }
      if (elementProps.typeToValidate !== 'repeat-password' && elementProps.typeToValidate !== 'url' && elementProps.typeToValidate !== 'file'
      && !validation.test(elementProps.elementValue)) {
        this.addError(element);
        this.addErrorProps(elementProps);
        return;
      }

      if (elementProps.typeToValidate === 'repeat-password') {
        const valid = this.validateRepeatedPassword(elementProps.elementValue, validation);
        if (!valid) {
          this.addError(element);
          this.addErrorProps(elementProps);
          return;
        }
      }
      if (elementProps.typeToValidate === 'url') {
        const valid = this.validateURL(elementProps.elementValue);
        if (!valid) {
          this.addError(element);
          this.addErrorProps(elementProps);
          return;
        }
      }
    }

    elementProps.elementMessage.classList.remove('error', 'guide');
    elementProps.elementMessage.innerText = '';
    element.classList.remove('error');
  }, // END validateElement

  guideInput(element) {
    const elementProps = this.prepareElementProps(element);
    if (element.classList.contains('error', 'ring-primary')) this.removeError(element);
    if (elementProps.elementMessage.classList.contains('error', 'text-primary')) this.removeErrorProps(elementProps);

    if (!elementProps.elementMessage.classList.contains('guide')) {
      elementProps.elementMessage.classList.add('guide');
      elementProps.elementMessage.innerText = elementProps.helpMessage;
    }
  }, // END guideInput

  removeError(element) {
    element.classList.remove('error', 'ring-primary');
    element.classList.add('ring-light');
  }, // END removeError

  removeErrorProps(elementProps) {
    elementProps.elementMessage.classList.remove('error', 'text-primary');
  }, // END addError

  addError(element) {
    console.log('adding error');
    element.classList.remove('ring-light');
    element.classList.add('error', 'ring-primary');
  }, // END addError

  addErrorProps(elementProps) {
    console.log('adding error props');
    console.log(elementProps);
    console.log(elementProps.elementMessage);
    elementProps.elementMessage.classList.remove('guide');
    elementProps.elementMessage.classList.add('error', 'text-primary');
    elementProps.elementMessage.innerText = elementProps.errorMessage;
    console.log(elementProps.elementMessage);
  }, // END addErrorProps

  activateInput(element) {
    element.removeAttribute('readonly');
  }, // END activateInput

  validateURL(string) {
    console.log(string);
    try {
      new URL(string);
      return true;
    } catch (error) {
      return false;
    }
  }, // END validateURL

  validateRepeatedPassword(string, validation) {
    return string === validation;
  }, // END validateURL

}; // END compValidator

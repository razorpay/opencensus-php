import {
  isBlank,
  isEmail,
  isValidPhone
} from './rzp-utils'

// let validate = validator({
//   firstName: {
//     presence: true,
//     message: 'Please provide the first name'
//   },

//   email: {
//     presence: true,
//     type: 'email',
//     messages: {
//       presence: 'please provide the email',
//       type: 'Email is invalid'
//     }
//   },

//   contact: {
//     presence: true,
//     length: {
//       min: 10,
//       max: 15,
//       message: 'Number should be btw 10 & 15'
//     },
//     message: 'Can\'t be blank'
//   }
// })

// console.log(validate(values))

const DEFAULT_VALIDATION_MSGS = {
  presence: 'Required',
  type: 'Invalid'
}

const presenceValidator = (value, message) => {
  return isBlank(value) ? message : null
}

const typeValidator = (value, message, type) => {
  let errored = false
  switch (type) {
    case 'email':
      errored = !isEmail(value)
      break
    case 'contact':
    case 'phone':
      errored = !isValidPhone(value)
      break
  }
  return errored ? message : null
}

const getValidatorMsg = (validator, key) => {
  if (validator.messages && validator.messages[key]) {
    return validator.messages[key]
  }

  if (validator.message) {
    return validator.message
  }

  return DEFAULT_VALIDATION_MSGS[key] || 'Some validation went wrong'
}

const SUPPORTED_VALIDATORS = {
  presence: presenceValidator,
  type: typeValidator
}

export default (validators) => {
  return (values) => {
    return Object.keys(validators).reduce((errors, validatorKey) => {
      let value = values[validatorKey]
      let validator = validators[validatorKey]

      Object.keys(validator).forEach((key) => {
        if (key === 'message' || key === 'messages' || errors[validatorKey]) {
          return
        }

        let error = SUPPORTED_VALIDATORS[key](value, getValidatorMsg(validator, key), validator[key])
        if (error) {
          errors[validatorKey] = error
        }
      })

      return errors
    }, {})
  }
}

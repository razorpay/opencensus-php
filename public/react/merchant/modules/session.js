class Session {
  session = null

  constructor(data) {
    this.initialize(data)
  }

  initialize(data) {
    this.session = data
    return this
  }

  get isAuthenticated() {
    return !!this.session
  }

  get currentMode() {
    return 'test' // TODO: need to handle from localstorage
  }
}

export default new Session()

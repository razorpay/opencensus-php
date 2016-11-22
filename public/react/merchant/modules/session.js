class Session {
  session = null

  constructor(data) {
    this.initialize(data)
  }

  initialize(data = {}) {
    this.session = data
    return this
  }

  get isAuthenticated() {
    return !!this.session.identity
  }

  get currentMode() {
    return this.session.modeFactory.getMode()
  }
}

export default new Session()

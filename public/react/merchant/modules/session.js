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
    return !!this.session.user
  }

  get currentMode() {
    return this.session.modeFactory.getMode()
  }

  get isLiveMode() {
    return this.currentMode === 'live'
  }

  get userRole() {
    let user = this.session.user
    return user.merchants[user.id].pivot.role
  }
}

export default new Session()

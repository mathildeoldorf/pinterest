class AUTH {
  async handleAuthentication() {
    try {
      this.url = 'server/api-get-authentication';
      this.response = await fetch(this.url);

      const data = await this.response.json();
      return data;
    } catch (error) {
      return false;
    }
  }
}

export default AUTH;

class HTTP {
  // HTTP GET Request
  async get(url) {
    // Awaiting for fetch response
    this.response = await fetch(url);

    // Awaiting for response.json()
    const data = await this.response.json();

    // Returning result data
    return data;
  }

  // HTTP POST Request
  async post(url, formData) {
    // Awaiting for fetch response and
    // defining method, headers and body

    this.response = await fetch(url, {
      method: 'POST',
      // headers: {
      //     'Content-type': 'application/json'
      // },
      body: formData,
    });

    // Awaiting response.json()
    const data = await this.response.json();

    // Returning result data
    return data;
  }
}

export default HTTP;

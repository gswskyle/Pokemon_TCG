const API = {
  token: "",

  get(url) {
    return fetch(url).then(r => r.json());
  },

  post(url, data) {
    data.token = this.token;
    return fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data)
    }).then(r => r.json());
  },

  put(url, data) {
    data.token = this.token;
    return fetch(url, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data)
    }).then(r => r.json());
  },

  del(url, data) {
    data.token = this.token;
    return fetch(url, {
      method: "DELETE",
      body: new URLSearchParams(data)
    }).then(r => r.json());
  }
};

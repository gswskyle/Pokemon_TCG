const app = Vue.createApp({
    data() {
        return {
            cards: [],
            search: "",
            sort: "release_desc",
            loggedIn: false,
            isManager: false,
            showWishlist: false,
            newCardId: "",
            managerMessage: ""
        };
    },
    mounted() {
        this.fetchCards();
        this.checkLogin();
    },
    methods: {
        checkLogin() {
            API.get("auth.php?action=session").then(data => {
                this.loggedIn = data.loggedIn;
                this.isManager = data.role === "manager";
                API.token = data.token || "";
            });
        },
        logout() {
            API.get("auth.php?action=logout").then(() => {
                this.loggedIn = false;
                window.location.href = "index.html";
            });
        },
        fetchCards() {
            let url = `cards.php?sort=${this.sort}&search=${this.search}`;
            if (this.showWishlist) {
                url = `wishlist.php?sort=${this.sort}&search=${this.search}`;
            }
            API.get(url).then(data => this.cards = data);
        },
        addCard() {
            if (!this.newCardId) return;
            API.post("cards.php", { api_id: this.newCardId }).then(res => {
                this.managerMessage = res.success ? "Card added successfully!" : "Failed to add card.";
                this.fetchCards();
            });
        },
        deleteCard(id) {
            if (!confirm("Are you sure you want to delete this card?")) return;
            API.del("cards.php", { id }).then(res => {
                if (res.success) alert("Card deleted.");
                else alert("Failed to delete card.");
                this.fetchCards();
            });
        }
    }
});
app.mount("#app");

const app = Vue.createApp({
    data() {
        return {
            card: {},
            reviews: [],
            newReview: "",
            newComments: {},
            userRating: 0,
            inWishlist: false,
            loggedIn: false,
            isManager: false,
            currentUserId: null,

            showModal: false,
            modalType: "",
            modalContent: "",
            modalId: null
        };
    },

    mounted() {
        const params = new URLSearchParams(window.location.search);
        this.cardId = params.get("id");
        this.loadCard();
        this.checkLogin();
    },

    methods: {
        checkLogin() {
            API.get("auth.php?action=session").then(data => {
                this.loggedIn = data.loggedIn;
                this.isManager = data.role === "manager";
                this.currentUserId = data.user_id || null;
                API.token = data.token || "";
            });
        },

        loadCard() {
            API.get(`cards.php?id=${this.cardId}`).then(data => {
                this.card = data.card || {};
                this.reviews = data.reviews || [];
                this.userRating = Number(data.userRating || 0);
                this.inWishlist = data.inWishlist || false;
            });
        },

        addReview() {
            if (!this.newReview.trim()) return;
            API.post("reviews.php", { card_id: this.cardId, content: this.newReview })
            .then(() => {
                this.newReview = "";
                this.loadCard();
            });
        },

        addComment(reviewId) {
            if (!this.newComments[reviewId]?.trim()) return;
            API.post("comments.php", { review_id: reviewId, content: this.newComments[reviewId] })
            .then(() => {
                this.newComments[reviewId] = "";
                this.loadCard();
            });
        },

        editReview(review) {
            this.showModal = true;
            this.modalType = "Review";
            this.modalContent = review.content;
            this.modalId = review.id;
        },

        editComment(comment) {
            this.showModal = true;
            this.modalType = "Comment";
            this.modalContent = comment.content;
            this.modalId = comment.id;
        },

        saveEdit() {
            if (this.modalType === "Review") {
                API.put("reviews.php", { review_id: this.modalId, content: this.modalContent })
                .then(() => {
                    this.cancelEdit();
                    this.loadCard();
                });
            } else if (this.modalType === "Comment") {
                API.put("comments.php", { comment_id: this.modalId, content: this.modalContent })
                .then(() => {
                    this.cancelEdit();
                    this.loadCard();
                });
            }
        },

        cancelEdit() {
            this.showModal = false;
            this.modalContent = "";
            this.modalId = null;
        },

        deleteReview(id) {
            if (!confirm("Delete this review?")) return;
            API.del("reviews.php", { id }).then(() => this.loadCard());
        },

        deleteComment(id) {
            if (!confirm("Delete this comment?")) return;
            API.del("comments.php", { id }).then(() => this.loadCard());
        },

        rateCard(stars) {
            API.post("rating.php", { card_id: this.cardId, rating: stars }).then(() => this.loadCard());
        },

        removeRating() {
            API.del("rating.php", { card_id: this.cardId }).then(() => this.loadCard());
        },

        toggleWishlist() {
            const method = this.inWishlist ? API.del : API.post;
            method("wishlist.php", { card_id: this.cardId }).then(() => this.loadCard());
        },

        canEdit(userId) {
            return this.loggedIn && this.currentUserId === userId;
        },

        backHome() {
            window.location.href = "index.html";
        }
    }
});

app.mount("#app");

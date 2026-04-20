document.addEventListener("click", function(event) {

    const card = event.target.closest(".sessao-card");

    if (card) {
        if (card.id === "guardaRoupaCard") {
            window.location.href = "guardaRoupa.html";
        }

        if (card.id === "mesaEstarCard") {
            window.location.href = "mesaEstar.html";
        }

        if (card.id === "sofaCard") {
            window.location.href = "../sofa.html";
        }
    }
});
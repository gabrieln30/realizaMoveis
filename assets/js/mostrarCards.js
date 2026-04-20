const btnVerMais = document.getElementById("btnVerMais");
const btnVerMenos = document.getElementById("btnVerMenos");

btnVerMais.addEventListener("click", mostrarCards);
btnVerMenos.addEventListener("click", esconderCards);

function mostrarCards() {
  const cards = document.querySelectorAll(".sessao-card.hidden");

  cards.forEach(card => {
    card.classList.remove("hidden");
  });

  btnVerMais.classList.add("hidden");
  btnVerMenos.classList.remove("hidden");
}

function esconderCards() {
  const todosCards = document.querySelectorAll(".sessao-card");

  // define quantos ficam visíveis (ex: primeiros 4)
  todosCards.forEach((card, index) => {
    if (index >= 4) {
      card.classList.add("hidden");
    }
  });

  btnVerMais.classList.remove("hidden");
  btnVerMenos.classList.add("hidden");

  // opcional: subir a tela suavemente
  window.scrollTo({
    top: document.querySelector(".sessoes-general").offsetTop,
    behavior: "smooth"
  });
}
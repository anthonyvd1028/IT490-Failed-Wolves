(function () {
  // get query param ?book=...
  const params = new URLSearchParams(window.location.search);
  const book = params.get("book") || "Sportsbook";
  document.getElementById("bookTitle").textContent = book;

  // highlight active sportsbook in the second bar
  const links = document.querySelectorAll(".subnav a");
  links.forEach(a => {
    const url = new URL(a.href, window.location.origin);
    const b = new URLSearchParams(url.search).get("book");
    if (b && b.toLowerCase() === book.toLowerCase()) {
      a.classList.add("active");
    }
  });

  // set nfl link to go to a future page
  const nflLink = document.getElementById("nflLink");
  if (nflLink) {
    nflLink.href = "#"; // insert NFL page here when made
  }
})();


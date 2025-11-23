document.addEventListener("DOMContentLoaded", () => {
  const rows = document.querySelectorAll(".am-table tbody tr");
  const fNome = document.getElementById("filtro-nome");
  const fAnno = document.getElementById("filtro-anno");
  const fSesso = document.getElementById("filtro-sesso");
  const fPadre = document.getElementById("filtro-padre");
  const fMadre = document.getElementById("filtro-madre");
  const fDisp = document.getElementById("filtro-disponibilita");
  const btnCerca = document.getElementById("btn-cerca");
  const btnReset = document.getElementById("btn-reset");
  const msgNessunRisultato = document.getElementById("nessun-risultato");

  function filtra() {
    const nome = fNome.value.toLowerCase().trim();
    const anno = fAnno.value.trim();
    const sesso = fSesso.value.toLowerCase().trim();
    const padre = fPadre.value.toLowerCase().trim();
    const madre = fMadre.value.toLowerCase().trim();
    const disp = fDisp.value.trim();

    let risultatiVisibili = 0;

    rows.forEach(row => {
      const cells = row.querySelectorAll("td");
      const rAnno = cells[0]?.textContent.trim() || "";
      const rNome = cells[1]?.textContent.toLowerCase().trim() || "";
      const rSesso = cells[2]?.textContent.trim().toUpperCase() || "";
      const rPadre = cells[3]?.textContent.toLowerCase().trim() || "";
      const rMadre = cells[4]?.textContent.toLowerCase().trim() || "";
      const rDisp = row.dataset.disp || "";

      let sessoMatch = true;
      if (sesso) {
        const val = sesso.toLowerCase();
        if (
          (val === "maschio" && rSesso !== "M") ||
          (val === "femmina" && rSesso !== "F") ||
          (val === "m" && rSesso !== "M") ||
          (val === "f" && rSesso !== "F")
        ) {
          sessoMatch = false;
        }
      }

      const match =
        (!nome || rNome.includes(nome)) &&
        (!anno || rAnno === anno) &&
        sessoMatch &&
        (!padre || rPadre.includes(padre)) &&
        (!madre || rMadre.includes(madre)) &&
        (!disp || rDisp === disp);

      row.style.display = match ? "" : "none";
      if (match) risultatiVisibili++;
    });

    // Mostra o nascondi il messaggio "nessun risultato"
    if (msgNessunRisultato) {
      msgNessunRisultato.style.display = risultatiVisibili === 0 ? "block" : "none";
    }
  }

  btnCerca.addEventListener("click", filtra);

  btnReset.addEventListener("click", () => {
    fNome.value = "";
    fAnno.value = "";
    fSesso.value = "";
    fPadre.value = "";
    fMadre.value = "";
    fDisp.value = "";
    rows.forEach(r => (r.style.display = ""));
    if (msgNessunRisultato) msgNessunRisultato.style.display = "none";
  });
});

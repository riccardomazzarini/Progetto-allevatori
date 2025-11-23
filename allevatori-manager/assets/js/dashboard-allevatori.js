document.addEventListener("DOMContentLoaded", function(){
    document.querySelectorAll(".am-tab-link").forEach(function(link){
        link.addEventListener("click", function(e){
            e.preventDefault();
            document.querySelectorAll(".am-tab-link").forEach(l => l.classList.remove("active"));
            document.querySelectorAll(".am-tab").forEach(tab => tab.classList.remove("active"));
            this.classList.add("active");
            document.getElementById("am-tab-" + this.dataset.tab).classList.add("active");
        });
    });
});

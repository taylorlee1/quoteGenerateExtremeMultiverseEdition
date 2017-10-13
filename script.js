
(function() {
  
  var Model = {
    quote : {
      id : -100,
      quote : '',
      author : '',
      likes : 0,
    },

    request : function(reqtype, req, data, cb) {
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
          cb(xhttp.responseText);
        }
      };
      xhttp.open(reqtype, "/whatIsThyBidding.php?" + req, true);
      xhttp.send(JSON.stringify(data));
    },

    getQuote : function() {
      this.request("GET", 'getquote=' + Model.quote.id, null, this.getQuoteCB);
    },

    getQuoteCB : function(datum) {
      Model.quote = JSON.parse(datum);
      Model.quote.id = parseInt(Model.quote.id);
      Model.quote.likes = parseInt(Model.quote.likes);
      console.log("Model.getQuoteCB() " + JSON.stringify(Model.quote));
      Ctrl.showQuote(Model.quote);
    },
 
    addNewQuote : function(data) {
      this.request("POST", 'newquote=data', data, this.addNewQuoteCB);
    },

    addNewQuoteCB : function(data) {
      console.log("Model.addNewQuoteCB() " + data);
      Ctrl.addDone(data);
    },

    likeIncrement : function() {
      console.log("Model.likeIncrement() " + JSON.stringify(Model.quote));
      this.request("GET", 'incrementlikes=' + Model.quote.id, null, this.likeChange);
    },

    likeDecrement : function() {
      console.log("Model.likeDecrement() " + JSON.stringify(Model.quote));
      this.request("GET", 'decrementlikes=' + Model.quote.id, null, this.likeChange);
    },

    likeChange : function(datum) {
      datum = JSON.parse(datum);
      console.log("Model.likeChange() " + JSON.stringify(datum));
      Model.quote.likes = parseInt(datum.likes);
      console.log("Model.likeChange() " + JSON.stringify(Model.quote));
      Ctrl.showQuote(Model.quote);
    }



  };


  var Ctrl = {
    init : function() {
      console.log("Ctrl.init()");
      View.init();
      this.refreshQuote();
    },

    refreshQuote : function() {
      Model.getQuote();
    },

    showQuote : function(datum) {
      View.showQuote(datum);
    },
    
    addNewQuote : function(data) {
      Model.addNewQuote(data);
    },

    likeQuote : function() {
      Model.likeIncrement();
    },

    dislikeQuote : function() {
      Model.likeDecrement();
    },

    addDone : function(result) {
      if (result === "OK") {
        alert("Thank you for the submission!");
      } else {
        alert("Could not add, sorry!");
      }

      View.hideNewQuoteForm();

    },


  };

  var View = {
    likesObj   : document.getElementById("likes"),
    quoteObj   : document.getElementById("quote"),
    authorObj  : document.getElementById("author"),
    addnewBtn  : document.getElementById("addnew"),
    refreshBtn : document.getElementById("refresh"),
    likeBtn    : document.getElementById("like"),
    dislikeBtn : document.getElementById("dislike"),
    tweetLink  : document.getElementById("tweetLink"),
    socialDiv  : document.getElementById("socialDiv"),
    quoteBoxBox         : document.getElementById("quotebox-box"),
    newQuoteForm        : document.getElementById("newQuoteForm"),
    newQuoteSubmitBtn   : document.getElementById("newQuoteSubmit"),
    newQuoteCancelBtn   : document.getElementById("newQuoteCancel"),
    newQuoteAuthorInput : document.getElementById("newQuoteAuthor"),
    newQuoteTextInput   : document.getElementById("newQuoteText"),

    init : function() {
      console.log("View.init()");
      this.addnewBtn.onclick = this.addNewQuote;
      this.refreshBtn.onclick = this.refreshQuote;
      this.likeBtn.onclick = this.likeQuote;
      this.dislikeBtn.onclick = this.dislikeQuote;
      this.newQuoteSubmitBtn.onclick = this.submitNewQuote;
      this.newQuoteCancelBtn.onclick = this.hideNewQuoteForm;
    },

    clearForm : function() {
      this.newQuoteAuthorInput.value = '';
      this.newQuoteAuthorInput.blur();
      this.newQuoteTextInput.value = '';
      this.newQuoteTextInput.blur();
    },

    refreshQuote : function() {
      Ctrl.refreshQuote();
    },

    addNewQuote : function() {
      View.showNewQuoteForm();
    },

    showNewQuoteForm : function() {
      this.newQuoteForm.classList.remove("hidden");
      this.quoteBoxBox.classList.add("hidden");
      this.socialDiv.classList.add("vis-hidden");
    },

    hideNewQuoteForm : function() {
      View.clearForm();
      View.newQuoteForm.classList.add("hidden");
      View.quoteBoxBox.classList.remove("hidden");
      View.socialDiv.classList.remove("vis-hidden");
    },

    likeQuote : function() {
      Ctrl.likeQuote();
    },

    dislikeQuote : function() {
      Ctrl.dislikeQuote();
    },

    showQuote : function(quote) {
      this.quoteObj.innerHTML = quote.quote;
      this.authorObj.innerHTML = quote.author;
      if (quote.likes > 0) {
        this.likesObj.innerHTML = '+' + quote.likes;
      } else {
        this.likesObj.innerHTML = quote.likes;
      }
      this.tweetLink.href = 'https://twitter.com/intent/tweet?text=';
      this.tweetLink.href += encodeURIComponent(quote.quote) + 
        ' -- ' + encodeURIComponent(quote.author); 
    },
    
    submitNewQuote : function() {
      //console.log(View.newQuoteAuthorInput.value + " " + View.newQuoteTextInput.value); 
      Ctrl.addNewQuote({ 
        author : View.newQuoteAuthorInput.value,
        quote : View.newQuoteTextInput.value,
        });
    },
  };

  Ctrl.init();
})();

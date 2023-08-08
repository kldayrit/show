define(['jquery'], function($) {
    // Cosine similarity with tf-idf for crude semantic analysis
    const stopWords = ["the", "is", "in", "a", "an", "at", "from"];
    // Helper function to tokenize the text and remove punctuation and stop words
  /**
   * @param {string}text
   * @return {array}
   */
  function tokenize(text) {
    return text.toLowerCase().match(/\b\w+\b/g).filter(word => !stopWords.includes(word));
  }

  // Helper function to calculate the term frequency (TF) of each word in a document
  /**
   * @param {array}document
   * @return {array}
   */
  function calculateTF(document) {
    const wordFrequency = {};
    const words = tokenize(document);

    for (const word of words) {
      wordFrequency[word] = (wordFrequency[word] || 0) + 1;
    }

    const totalWords = words.length;
    const tf = {};
    for (const word in wordFrequency) {
      tf[word] = wordFrequency[word] / totalWords;
    }

    return tf;
  }

  // Helper function to calculate the inverse document frequency (IDF) for each word
  /**
   * @param {array} textArray
   * @return {array}
   */
  function calculateIDF(textArray) {
    const documentFrequency = {};
    const totalDocuments = textArray.length;

    for (const document of textArray) {
      const words = tokenize(document);
      for (const word of words) {
        documentFrequency[word] = (documentFrequency[word] || 0) + 1;
      }
    }

    const idf = {};
    for (const word in documentFrequency) {
      idf[word] = Math.log(totalDocuments / (documentFrequency[word] + 1)); // Adding 1 to avoid division by zero
    }

    return idf;
  }

  // Helper function to calculate the TF-IDF for a document
  /**
   * @param {array} tf
   * @param {array} idf
   * @return {array}
   */
  function calculateTFIDF(tf, idf) {
    const tfidf = {};
    for (const word in tf) {
      tfidf[word] = tf[word] * idf[word];
    }
    return tfidf;
  }

  // Helper function to calculate the dot product of two vectors
  /**
   * @param {int} vectorA
   * @param {int} vectorB
   * @return {int}
   */
  function dotProduct(vectorA, vectorB) {
    let product = 0;
    for (const word in vectorA) {
      if (vectorB.hasOwnProperty(word)) {
        product += vectorA[word] * vectorB[word];
      }
    }
    return product;
  }

  // Helper function to calculate the magnitude of a vector
  /**
   * @param {int} vector
   * @return {int}
   */
  function vectorMagnitude(vector) {
    let sumOfSquares = 0;
    for (const word in vector) {
      sumOfSquares += vector[word] ** 2;
    }
    return Math.sqrt(sumOfSquares);
  }

  // Helper function to calculate the cosine similarity between two documents
  /**
   * @param {string} documentA
   * @param {string} documentB
   * @param {array} idf
   * @return {int}
   */
  function cosineSimilarity(documentA, documentB, idf) {
    const tfA = calculateTF(documentA);
    const tfB = calculateTF(documentB);

    const tfidfA = calculateTFIDF(tfA, idf);
    const tfidfB = calculateTFIDF(tfB, idf);

    const dotProductAB = dotProduct(tfidfA, tfidfB);
    const magnitudeA = vectorMagnitude(tfidfA);
    const magnitudeB = vectorMagnitude(tfidfB);

    return dotProductAB / (magnitudeA * magnitudeB);
  }
    // FROM https://www.npmjs.com/package/string-similarity?activeTab=code
    /**
     * @param {string} first string
     * @param {string} second
     * @returns {int}
     */
    function compareTwoStrings(first, second) {
        first = first.replace(/\s+/g, '');
        second = second.replace(/\s+/g, '');

        if (first === second) {
            return 1;
        } // Identical or empty
        if (first.length < 2 || second.length < 2) {
            return 0;
        } // If either is a 0-letter or 1-letter string

        let firstBigrams = new Map();
        for (let i = 0; i < first.length - 1; i++) {
            const bigram = first.substring(i, i + 2);
            const count = firstBigrams.has(bigram)
                ? firstBigrams.get(bigram) + 1
                : 1;

            firstBigrams.set(bigram, count);
        }

        let intersectionSize = 0;
        for (let i = 0; i < second.length - 1; i++) {
            const bigram = second.substring(i, i + 2);
            const count = firstBigrams.has(bigram)
                ? firstBigrams.get(bigram)
                : 0;

            if (count > 0) {
                firstBigrams.set(bigram, count - 1);
                intersectionSize++;
            }
        }

        return (2.0 * intersectionSize) / (first.length + second.length - 2);
    }

    /**
     * @param {string} mainString
     * @param {string} targetStrings
     * @return {array}
     */
    function findBestMatch(mainString, targetStrings) {
        if (!areArgsValid(mainString, targetStrings)) {
 throw new Error('Bad arguments: First argument should be a string, second should be an array of strings');
}

        const ratings = [];
        let bestMatchIndex = 0;

        for (let i = 0; i < targetStrings.length; i++) {
            const currentTargetString = targetStrings[i];
            const currentRating = compareTwoStrings(mainString, currentTargetString);
            ratings.push({target: currentTargetString, rating: currentRating});
            if (currentRating > ratings[bestMatchIndex].rating) {
                bestMatchIndex = i;
            }
        }


        const bestMatch = ratings[bestMatchIndex];

        return {ratings: ratings, bestMatch: bestMatch, bestMatchIndex: bestMatchIndex};
    }

    /**
     * @param {string} mainString
     * @param {string} targetStrings
     * @returns {boolean}
     */
    function areArgsValid(mainString, targetStrings) {
        if (typeof mainString !== 'string') {
        return false;
        }
                if (!Array.isArray(targetStrings)) {
        return false;
        }
                if (!targetStrings.length) {
        return false;
        }
        if (targetStrings.find(function(s) {
        return typeof s !== 'string';
        })) {
        return false;
        }
        return true;
    }

    return {
        'initialize': function() {
            $('.check').bind('click', function() {
                var value = $(this).attr("value");
                var textArray = value.split("||");
                var selected = textArray[0];
                textArray.shift();
                var rat = findBestMatch(textArray[selected], textArray.filter(function(x) {
                            return textArray.indexOf(x) != selected;
                            }));
                var compare = rat.ratings;
                $('tr').css('background-color', '');
                $('.' + selected).css('background-color', '');
                $('#' + selected).closest('tr').css('background-color', 'rgb(219, 255, 219)');
                for (let i = 0; i < textArray.length; i++) {
                    $('.' + i).html('--');
                }
                compare.forEach(function(item) {
                    if (item.rating > 0.6) {
                        $('.' + textArray.indexOf(item.target)).css('background-color', 'rgb(255, 189, 182)');

                    } else {
                        $('.' + textArray.indexOf(item.target)).css('background-color', '');
                    }
                    $('.' + textArray.indexOf(item.target)).html(Math.round(item.rating * 100 * 100) / 100 + '% similar');
                });
                const idf = calculateIDF(textArray);
                const documentA = textArray[selected];
                for (let j = 0; j < textArray.length; j++) {
                    $('.sem' + j).css('background-color', '');
                    if (j == selected) {
                        $('.sem' + j).html('--');
                        continue;
                    }
                    const documentB = textArray[j];
                    const similarity = cosineSimilarity(documentA, documentB, idf);
                    if (isNaN(similarity)) {
                        similarity = 0;
                    }
                    if (similarity > 0.6) {
                        $('.sem' + j).css('background-color', 'rgb(255, 189, 182)');
                    }
                    $('.sem' + j).html(Math.round(similarity * 100 * 100) / 100 + '% similar');
                }
            });
            t(".checka").bind("click", function () {
              var n = t(this).attr("value").split("||"),
                  o = n[0];
              n.shift();
              var c = a(
                  n[o],
                  n.filter(function (t) {
                      return n.indexOf(t) != o;
                  })
              ).ratings;
              t("tr").css("background-color", ""),
                  t(".a" + o).css("background-color", ""),
                  t("#a" + o)
                      .closest("tr")
                      .css("background-color", "rgb(219, 255, 219)");
              for (let r = 0; r < n.length; r++) t(".a" + r).html("--");
              c.forEach(function (r) {
                  r.rating > 0.6 ? t(".a" + n.indexOf(r.target)).css("background-color", "rgb(255, 189, 182)") : t(".a" + n.indexOf(r.target)).css("background-color", ""),
                      t(".a" + n.indexOf(r.target)).html(Math.round(100 * r.rating * 100) / 100 + "% similar");
              });
              const s = (function (t) {
                      const n = {},
                          o = t.length;
                      for (const o of t) {
                          const t = r(o);
                          for (const r of t) n[r] = (n[r] || 0) + 1;
                      }
                      const c = {};
                      for (const t in n) c[t] = Math.log(o / (n[t] + 1));
                      return c;
                  })(n),
                  i = n[o];
              for (let r = 0; r < n.length; r++) {
                  if ((t(".asem" + r).css("background-color", ""), r == o)) {
                      t(".asem" + r).html("--");
                      continue;
                  }
                  const c = e(i, n[r], s);
                  isNaN(c) && (c = 0), c > 0.6 && t(".asem" + r).css("background-color", "rgb(255, 189, 182)"), t(".asem" + r).html(Math.round(100 * c * 100) / 100 + "% similar");
              }
          });
        }
    };
});
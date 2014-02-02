
function CrosswordData() {
	var active_letter;
	var active_word;
	var LEFT;
	var RIGHT;
	var UP;
	var DOWN;
}

CrosswordData.splitWordAndGetLetter = function() {
	tmp = CrosswordData.active_letter.split('-');
	return tmp[2];
}

function store() {
	//store a cookie if there isn't already one
	//check the crossword
	//submit an ajax request to flag this cookie as having completed this crossword
	
}

function concentrate() {
    //Remove the board, show a single clue and the layout (and any letters)
    $("div#crossword").hide();
} 
 
var inputBind = function(event) 
{
	//$("div#information").html(//$("div#information").html()+new Date().getTime()+":CALL<br/>");
	letter = CrosswordData.splitWordAndGetLetter();

	if (event.which == CrosswordData.UP || event.which == CrosswordData.DOWN || event.which == CrosswordData.LEFT || event.which == CrosswordData.RIGHT) {
		new_letter = getSpace(CrosswordData.active_letter, event.which);
		//$("div#information").html(//$("div#information").html()+new Date().getTime()+":NEW/OLD="+new_letter+"/"+CrosswordData.active_letter+"<br/>");
		if (new_letter != CrosswordData.active_letter) {
			CrosswordData.active_letter = moveTo(new_letter, words_in_clue[CrosswordData.active_word]);
			split_letter = CrosswordData.active_letter.split('-');
			letter = split_letter[2];
		}
	} else if (event.which == 8) {
		$("input#"+CrosswordData.active_letter).val('');
		if (intersections[CrosswordData.active_letter]) {
			$("input#"+intersections[CrosswordData.active_letter]).val('');
		}
		if (letter > 1) {
			letter--;
			CrosswordData.active_letter = moveTo(split_word[0]+"-"+split_word[1]+"-"+(parseInt(letter)), words_in_clue[CrosswordData.active_word])
		} else if (letter == 1 && words_in_clue[CrosswordData.active_word].length > 1) {
			alert("Old word");
			$(words_in_clue[CrosswordData.active_word]).each(function (key, word) {
				if (word != CrosswordData.active_word) {
					moveTo(word+"-"+lengths[word], CrosswordData.active_word);
					return false;
				}
			});
		}
	} else {
		$("input#"+CrosswordData.active_letter).val(get_letter(event.which));
		if (intersections[CrosswordData.active_letter]) {
			$("input#"+intersections[CrosswordData.active_letter]).val(get_letter(event.which));
		}
		//move CrosswordData.RIGHT/CrosswordData.DOWN for keypress, CrosswordData.UP/CrosswordData.LEFT for backspace
		//$("div#information").html($("div#information").html()+new Date().getTime()+":LETT="+letter+"<br/>");
		
		if (letter < lengths[CrosswordData.active_word]) {
			letter++;
			CrosswordData.active_letter = moveTo(split_word[0]+"-"+split_word[1]+"-"+(parseInt(letter)), words_in_clue[CrosswordData.active_word]);
		} else if (letter == lengths[CrosswordData.active_word] && words_in_clue[CrosswordData.active_word].length > 1) {
			$(words_in_clue[CrosswordData.active_word]).each(function (key, word) {
				if (word != CrosswordData.active_word) {
					moveTo(word+"-1", CrosswordData.active_word);
					return false;
				}
			});
		}
	}
	return false;
}

var words = {};

function processOne(how) {
	if (typeof(CrosswordData.active_word) != 'undefined' && CrosswordData.active_word != "" && lengths[CrosswordData.active_word]) {
		//for (i = 1; i <= lengths[CrosswordData.active_word]; i++) {
		$.each(solutions[CrosswordData.active_word], function(index, letter) {
			key = CrosswordData.active_word+"-"+(index+1);
			if (how == 'cheat') {
				$("input#"+key).val(letter);
				if (intersections[key]) {
					$("input#"+intersections[key]).val(letter);
				}
			} else if (how == 'check') {
				if ($("input#"+key).val() != letter) {
					$("input#"+key).val('');
					if (intersections[key]) {
						$("input#"+intersections[key]).val('');
					}
				}
			}
		});
		//}
	}
}

function processAll(how) {
	start = new Date().getTime()
	//$("#active-clue").html(start + " - ");
	if (typeof(CrosswordData.active_word) != 'undefined') {
		old_active_word = CrosswordData.active_word;
		$.each(solutions, function(solution, letter) {
			solution_split = solution.split('-');
			CrosswordData.active_word = solution_split[0]+"-"+solution_split[1];
			processOne(how);
		});
		CrosswordData.active_word = old_active_word;
	}
	end = new Date().getTime();
	//$("#active-clue").append(end + " = " + (end - start));
}

function getSpace(from, direction) {
	var old_from = from;
	split_from = from.split('-');
	//if there's an intersection and the direction is perpendicular to the current clue
	if (
		(
			(split_from[1] == 'across' && (direction == CrosswordData.UP || direction == CrosswordData.DOWN)) ||
			(split_from[1] == 'down' && (direction == CrosswordData.LEFT || direction == CrosswordData.RIGHT))
		) && intersections[split_from[0]+"-"+split_from[1]+"-"+split_from[2]]
	) {
		//$("div#information").html(//$("div#information").html()+new Date().getTime()+":INT"+from);
		//Don't move in the next clue, just select the next clue
		//return getSpace(intersections[split_from[0]+"-"+split_from[1]+"-"+split_from[2]], direction);
		return intersections[split_from[0]+"-"+split_from[1]+"-"+split_from[2]];
	} else {
		if (
			(
				(
					(split_from[1] == 'across' && direction == CrosswordData.LEFT) || 
					(split_from[1] == 'down' && direction == CrosswordData.UP)
				) && parseInt(split_from[2]) == 1
			) || (
				(
					(split_from[1] == 'across' && direction == CrosswordData.RIGHT) || 
					(split_from[1] == 'down' && direction == CrosswordData.DOWN)
				) && parseInt(split_from[2]) == lengths[split_from[0]+"-"+split_from[1]]
			)
		) {
			//$("div#information").html(//$("div#information").html()+new Date().getTime()+":NOMOVE"+from);
			//TODO is there an extra word in this clue?
			return from;
		}
		if ((split_from[1] == 'across' && direction == CrosswordData.LEFT) || (split_from[1] == 'down' && direction == CrosswordData.UP)) {
			//$("div#information").html(//$("div#information").html()+new Date().getTime()+":BACK"+from);
			return split_from[0]+"-"+split_from[1]+"-"+(parseInt(split_from[2])-1);
		} else if ((split_from[1] == 'across' && direction == CrosswordData.RIGHT) || (split_from[1] == 'down' && direction == CrosswordData.DOWN)) {
			//$("div#information").html(//$("div#information").html()+new Date().getTime()+":FORWARD"+from);
			return split_from[0]+"-"+split_from[1]+"-"+(parseInt(split_from[2])+1);
		}
	}
	return from;
}

function checkSpace(place) {
	place_split = place.split('-');
	if (place_split[2] < 1) {
		return false;
	}
	clue = place_split[0]+"-"+place_split[1];
	if (place_split[2] > lengths[clue]) {
		return false;
	}
	return true;
}

function moveTo(new_letter, old_word) {
	//$("div#information").html(//$("div#information").html()+new Date().getTime()+":KEY"+new_letter+"<br/>");
	$("input#"+new_letter).focus();
	split_letter = new_letter.split('-');
	word = split_letter[0]+"-"+split_letter[1];
	CrosswordData.active_letter = new_letter;
	if (word != old_word) {
		//$("div#information").html(//$("div#information").html()+new Date().getTime()+":HLT"+word+"/"+split_letter[2]+"<br/>");
		clearAllExcept([word]);
		highlightWord(word, split_letter[2]);
	}
	//$("div#information").html($("div#information").html()+new Date().getTime()+":DST"+new_letter+"<br/>");
	return new_letter;
}

function clearAllExcept(exceptions) {
	tmp = [];
	if (typeof(exceptions) == 'object') {
		tmp.push(exceptions[0]);
	}
	avoid = "not:(#"+tmp.join("):not:(#")+")";
	$("div#crossword div:"+avoid+" > input").removeClass("highlight").css("z-index",split_word[0]);
//.unbind('keyCrosswordData.UP');
	avoid = "not:(#"+tmp.join("-clue):not:(#")+"-clue)";
	$("div.clue:"+avoid).removeClass("darken");
}

function highlightWord(word, letter) {
	CrosswordData.active_word=word;
	////$("div#information").html(//$("div#information").html()+new Date().getTime()+":TOP"+word+"-"+letter+"<br/>");
	CrosswordData.active_letter = word+"-"+letter;
	split_word = word.split('-');
	
	clearAllExcept(words_in_clue[word]);
	clue = "";
	for (tmp in words_in_clue[word]) {
		if (typeof(words_in_clue[word][tmp]) != 'undefined') {
			//alert(words[word]);
			$("div#"+words_in_clue[word][tmp]+".word input").addClass("highlight").css("z-index","99");
			$("div#"+words_in_clue[word][tmp]+"-clue").addClass("darken");
			clue += $("div#"+words_in_clue[word][tmp]+"-clue").html()+"\n</br>";
		}
	}
	$("div#active-clue").html(clue);
	//search through all of words_in_clue to see if this word is in any others
	//TODO Make this a jQ method call so it can be incorporated into the backspace multi-word function above.
    $("div#active-word").html($("div#" + word).html());
    $("div#active-word.input").removeAttr('id').keydown(inputBind);
	for (tmp in words_in_clue) {
		if (typeof(words_in_clue[tmp]) != 'undefined') {
			if (tmp != word && words_in_clue[tmp].length > 1) {
				doneit = false;
				for (tmptmp in words_in_clue[tmp]) {
					if (typeof(words_in_clue[tmp][tmptmp]) != 'undefined') {
						if (words_in_clue[tmp][tmptmp] == word)	{
							for (doit in words_in_clue[tmp]) {
								$("div#"+words_in_clue[tmp][doit]+".word input").addClass("highlight").css("z-index","99");
								$("div#"+words_in_clue[tmp][doit]+"-clue").addClass("darken");
								doneit = true;
							}
							if (doneit) {
								break;
							}
						}
					}
				}
			}
		}
	}
}

function get_letter(which) {
	switch(which) {
	case 65:
		return 'A';
	case 66:
		return 'B';
	case 67:
		return 'C';
	case 68:
		return 'D';
	case 69:
		return 'E';
	case 70:
		return 'F';
	case 71:
		return 'G';
	case 72:
		return 'H';
	case 73:
		return 'I';
	case 74:
		return 'J';
	case 75:
		return 'K';
	case 76:
		return 'L';
	case 77:
		return 'M';
	case 78:
		return 'N';
	case 79:
		return 'O';
	case 80:
		return 'P';
	case 81:
		return 'Q';
	case 82:
		return 'R';
	case 83:
		return 'S';
	case 84:
		return 'T';
	case 85:
		return 'U';
	case 86:
		return 'V';
	case 87:
		return 'W';
	case 88:
		return 'X';
	case 89:
		return 'Y';
	case 90:
		return 'Z';
	}
}

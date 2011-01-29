function processOne(how) {
	if (typeof(active_word) != 'undefined' && active_word != "" && lengths[active_word]) {
		for (i = 1; i <= lengths[active_word]; i++) {
			key = active_word+"-"+i;
			if (how == 'cheat') {
				$("input#"+key).val(solutions[key]);
				if (intersections[key]) {
					$("input#"+intersections[key]).val(solutions[key]);
				}
			} else if (how == 'check') {
				if ($("input#"+key).val() != solutions[key]) {
					$("input#"+key).val('');
					if (intersections[key]) {
						$("input#"+intersections[key]).val('');
					}
				}
			}
		}
	}
}

function processAll(how) {
	if (typeof(active_word) != 'undefined') {
		old_active_word = active_word;
		for(solution in solutions) {
			solution_split = solution.split('-');
			active_word = solution_split[0]+"-"+solution_split[1];
			processOne(how);
		}
		active_word = old_active_word;
	}
}

function getSpace(from, direction) {
	var LEFT = 37;
	var RIGHT = 39;
	var UP = 38;
	var DOWN = 40;
	var old_from = from;
	split_from = from.split('-');
	//if there's an intersection and the direction is perpendicular to the current clue
	if (
		(
			(split_from[1] == 'across' && (direction == UP || direction == DOWN)) ||
			(split_from[1] == 'down' && (direction == LEFT || direction == RIGHT))
		) && intersections[split_from[0]+"-"+split_from[1]+"-"+split_from[2]]
	) {
		//$("div#information").html(//$("div#information").html()+new Date().getTime()+":INT"+from);
		return getSpace(intersections[split_from[0]+"-"+split_from[1]+"-"+split_from[2]], direction);
	} else {
		if (
			(
				(
					(split_from[1] == 'across' && direction == LEFT) || 
					(split_from[1] == 'down' && direction == UP)
				) && parseInt(split_from[2]) == 1
			) || (
				(
					(split_from[1] == 'across' && direction == RIGHT) || 
					(split_from[1] == 'down' && direction == DOWN)
				) && parseInt(split_from[2]) == lengths[split_from[0]+"-"+split_from[1]]
			)
		) {
			//$("div#information").html(//$("div#information").html()+new Date().getTime()+":NOMOVE"+from);
			return from;
		}
		if ((split_from[1] == 'across' && direction == LEFT) || (split_from[1] == 'down' && direction == UP)) {
			//$("div#information").html(//$("div#information").html()+new Date().getTime()+":BACK"+from);
			return split_from[0]+"-"+split_from[1]+"-"+(parseInt(split_from[2])-1);
		} else if ((split_from[1] == 'across' && direction == RIGHT) || (split_from[1] == 'down' && direction == DOWN)) {
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
	$("input#"+new_letter).select();
	split_letter = new_letter.split('-');
	word = split_letter[0]+"-"+split_letter[1];
	if (word != old_word) {
		//$("div#information").html(//$("div#information").html()+new Date().getTime()+":HLT"+word+"/"+split_letter[2]+"<br/>");
		highlightWord(word, split_letter[2]);
	}
	//$("div#information").html($("div#information").html()+new Date().getTime()+":DST"+new_letter+"<br/>");
	return new_letter;
}

function highlightWord(word, letter) {
	active_word=word;
	var LEFT = 37;
	var RIGHT = 39;
	var UP = 38;
	var DOWN = 40;
	////$("div#information").html(//$("div#information").html()+new Date().getTime()+":TOP"+word+"-"+letter+"<br/>");
	var active_letter = word+"-"+letter;
	split_word = word.split('-');
	//remove highlight from all other elements
	$("div#crossword input").removeClass("highlight").css("z-index",split_word[0]);
	$("div.clue").removeClass("darken");
	$("input").unbind('keyup');
	$("div#"+word+".word input").addClass("highlight").css("z-index","99");
	$("div#"+word+"-clue").addClass("darken");
	$("div#"+word+".word input").keyup(function(event) {
		//$("div#information").html(//$("div#information").html()+new Date().getTime()+":CALL<br/>");
		var old_word = word;
		if (event.which == UP || event.which == DOWN || event.which == LEFT || event.which == RIGHT) {
			new_letter = getSpace(active_letter, event.which);
			//$("div#information").html(//$("div#information").html()+new Date().getTime()+":NEW/OLD="+new_letter+"/"+active_letter+"<br/>");
			if (new_letter != active_letter) {
				active_letter = moveTo(new_letter, word);
				split_letter = active_letter.split('-');
				letter = split_letter[2];
			}
		} else if (event.which == 8) {
			$("input#"+active_letter).val('');
			if (intersections[active_letter]) {
				$("input#"+intersections[active_letter]).val('');
			}
			if (letter > 1) {
				letter--;
				active_letter = moveTo(split_word[0]+"-"+split_word[1]+"-"+(parseInt(letter)), word)
			}
		} else {
			$("input#"+active_letter).val(get_letter(event.which));
			if (intersections[active_letter]) {
				$("input#"+intersections[active_letter]).val(get_letter(event.which));
			}
			//move right/down for keypress, up/left for backspace
			//$("div#information").html($("div#information").html()+new Date().getTime()+":LETT="+letter+"<br/>");
			if (letter < lengths[word]) {
				letter++;
				active_letter = moveTo(split_word[0]+"-"+split_word[1]+"-"+(parseInt(letter)), word);
			}
		}	
	});
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

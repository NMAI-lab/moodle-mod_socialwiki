console.log("search.js included");

var sampleTree = new tree();
sampleTree.addRoot();
sampleTree.addNode(0);
sampleTree.addNode(1);
sampleTree.addNode(1);
sampleTree.addNode(1);
sampleTree.addNode(2);
sampleTree.addNode(5);
sampleTree.addNode(6);
sampleTree.addNode(6);
sampleTree.addNode(2);
sampleTree.addNode(2);
sampleTree.addNode(3);
sampleTree.addNode(3);
sampleTree.addNode(3);
sampleTree.addNode(4);
sampleTree.addNode(4);
sampleTree.addRoot();
sampleTree.addNode(16);
sampleTree.addNode(17);
sampleTree.addNode(17);
sampleTree.addNode(16);
sampleTree.addNode(20);
sampleTree.addNode(21);
sampleTree.addNode(21);

console.log(searchResults);

mTree = new TreeControl(sampleTree, "socialwiki_content_area");

/*Trying to see how javascript array really work
var testArray = Array();
testArray['2-'] = 'hello';
testArray.push('hi');
console.log(testArray);*/


$(document).ready(function() {
    mTree.display();
});

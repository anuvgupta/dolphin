
window.projectVersion = 'master';

(function(root) {

    var bhIndex = null;
    var rootPath = '';
    var treeHtml = '        <ul>                <li data-name="namespace:Dolphin" class="opened">                    <div style="padding-left:0px" class="hd">                        <span class="glyphicon glyphicon-play"></span><a href="Dolphin.html">Dolphin</a>                    </div>                    <div class="bd">                                <ul>                <li data-name="class:Dolphin_Dolphin" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="Dolphin/Dolphin.html">Dolphin</a>                    </div>                </li>                </ul></div>                </li>                </ul>';

    var searchTypeClasses = {
        'Namespace': 'label-default',
        'Class': 'label-info',
        'Interface': 'label-primary',
        'Trait': 'label-success',
        'Method': 'label-danger',
        '_': 'label-warning'
    };

    var searchIndex = [
                    
            {"type": "Namespace", "link": "Dolphin.html", "name": "Dolphin", "doc": "Namespace Dolphin"},
            
            {"type": "Class", "fromName": "Dolphin", "fromLink": "Dolphin.html", "link": "Dolphin/Dolphin.html", "name": "Dolphin\\Dolphin", "doc": "&quot;Dolphin main class&quot;"},
                                                        {"type": "Method", "fromName": "Dolphin\\Dolphin", "fromLink": "Dolphin/Dolphin.html", "link": "Dolphin/Dolphin.html#method___construct", "name": "Dolphin\\Dolphin::__construct", "doc": "&quot;Constructor for a Dolphin&quot;"},
                    {"type": "Method", "fromName": "Dolphin\\Dolphin", "fromLink": "Dolphin/Dolphin.html", "link": "Dolphin/Dolphin.html#method___destruct", "name": "Dolphin\\Dolphin::__destruct", "doc": "&quot;Destructor for a Dolphin&quot;"},
                    {"type": "Method", "fromName": "Dolphin\\Dolphin", "fromLink": "Dolphin/Dolphin.html", "link": "Dolphin/Dolphin.html#method_connect", "name": "Dolphin\\Dolphin::connect", "doc": "&quot;Connect to MySQL&quot;"},
                    {"type": "Method", "fromName": "Dolphin\\Dolphin", "fromLink": "Dolphin/Dolphin.html", "link": "Dolphin/Dolphin.html#method_disconnect", "name": "Dolphin\\Dolphin::disconnect", "doc": "&quot;Disconnect from MySQL&quot;"},
                    {"type": "Method", "fromName": "Dolphin\\Dolphin", "fromLink": "Dolphin/Dolphin.html", "link": "Dolphin/Dolphin.html#method_set", "name": "Dolphin\\Dolphin::set", "doc": "&quot;Modify or create a child within a table&quot;"},
                    {"type": "Method", "fromName": "Dolphin\\Dolphin", "fromLink": "Dolphin/Dolphin.html", "link": "Dolphin/Dolphin.html#method_push", "name": "Dolphin\\Dolphin::push", "doc": "&quot;Add a new child with a unique ID to a table&quot;"},
                    {"type": "Method", "fromName": "Dolphin\\Dolphin", "fromLink": "Dolphin/Dolphin.html", "link": "Dolphin/Dolphin.html#method_get", "name": "Dolphin\\Dolphin::get", "doc": "&quot;Get data from a table based on child or query&quot;"},
                    {"type": "Method", "fromName": "Dolphin\\Dolphin", "fromLink": "Dolphin/Dolphin.html", "link": "Dolphin/Dolphin.html#method_error", "name": "Dolphin\\Dolphin::error", "doc": "&quot;Get logged errors&quot;"},
            
            
                                        // Fix trailing commas in the index
        {}
    ];

    /** Tokenizes strings by namespaces and functions */
    function tokenizer(term) {
        if (!term) {
            return [];
        }

        var tokens = [term];
        var meth = term.indexOf('::');

        // Split tokens into methods if "::" is found.
        if (meth > -1) {
            tokens.push(term.substr(meth + 2));
            term = term.substr(0, meth - 2);
        }

        // Split by namespace or fake namespace.
        if (term.indexOf('\\') > -1) {
            tokens = tokens.concat(term.split('\\'));
        } else if (term.indexOf('_') > 0) {
            tokens = tokens.concat(term.split('_'));
        }

        // Merge in splitting the string by case and return
        tokens = tokens.concat(term.match(/(([A-Z]?[^A-Z]*)|([a-z]?[^a-z]*))/g).slice(0,-1));

        return tokens;
    };

    root.Sami = {
        /**
         * Cleans the provided term. If no term is provided, then one is
         * grabbed from the query string "search" parameter.
         */
        cleanSearchTerm: function(term) {
            // Grab from the query string
            if (typeof term === 'undefined') {
                var name = 'search';
                var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
                var results = regex.exec(location.search);
                if (results === null) {
                    return null;
                }
                term = decodeURIComponent(results[1].replace(/\+/g, " "));
            }

            return term.replace(/<(?:.|\n)*?>/gm, '');
        },

        /** Searches through the index for a given term */
        search: function(term) {
            // Create a new search index if needed
            if (!bhIndex) {
                bhIndex = new Bloodhound({
                    limit: 500,
                    local: searchIndex,
                    datumTokenizer: function (d) {
                        return tokenizer(d.name);
                    },
                    queryTokenizer: Bloodhound.tokenizers.whitespace
                });
                bhIndex.initialize();
            }

            results = [];
            bhIndex.get(term, function(matches) {
                results = matches;
            });

            if (!rootPath) {
                return results;
            }

            // Fix the element links based on the current page depth.
            return $.map(results, function(ele) {
                if (ele.link.indexOf('..') > -1) {
                    return ele;
                }
                ele.link = rootPath + ele.link;
                if (ele.fromLink) {
                    ele.fromLink = rootPath + ele.fromLink;
                }
                return ele;
            });
        },

        /** Get a search class for a specific type */
        getSearchClass: function(type) {
            return searchTypeClasses[type] || searchTypeClasses['_'];
        },

        /** Add the left-nav tree to the site */
        injectApiTree: function(ele) {
            ele.html(treeHtml);
        }
    };

    $(function() {
        // Modify the HTML to work correctly based on the current depth
        rootPath = $('body').attr('data-root-path');
        treeHtml = treeHtml.replace(/href="/g, 'href="' + rootPath);
        Sami.injectApiTree($('#api-tree'));
    });

    return root.Sami;
})(window);

$(function() {

    // Enable the version switcher
    $('#version-switcher').change(function() {
        window.location = $(this).val()
    });

    
        // Toggle left-nav divs on click
        $('#api-tree .hd span').click(function() {
            $(this).parent().parent().toggleClass('opened');
        });

        // Expand the parent namespaces of the current page.
        var expected = $('body').attr('data-name');

        if (expected) {
            // Open the currently selected node and its parents.
            var container = $('#api-tree');
            var node = $('#api-tree li[data-name="' + expected + '"]');
            // Node might not be found when simulating namespaces
            if (node.length > 0) {
                node.addClass('active').addClass('opened');
                node.parents('li').addClass('opened');
                var scrollPos = node.offset().top - container.offset().top + container.scrollTop();
                // Position the item nearer to the top of the screen.
                scrollPos -= 200;
                container.scrollTop(scrollPos);
            }
        }

    
    
        var form = $('#search-form .typeahead');
        form.typeahead({
            hint: true,
            highlight: true,
            minLength: 1
        }, {
            name: 'search',
            displayKey: 'name',
            source: function (q, cb) {
                cb(Sami.search(q));
            }
        });

        // The selection is direct-linked when the user selects a suggestion.
        form.on('typeahead:selected', function(e, suggestion) {
            window.location = suggestion.link;
        });

        // The form is submitted when the user hits enter.
        form.keypress(function (e) {
            if (e.which == 13) {
                $('#search-form').submit();
                return true;
            }
        });

    
});



<script>
    // ----------------------------
    // Search Suggestions Script
    // ----------------------------
    $(document).ready(function(){
    var selectionMade = false;
    var debounceTimer;
    var lastSearchTerm = "";
    var currentRequest = null;
    var activeIndex = -1;

    function normalize(str) {
        return str.toLowerCase().replace(/[^a-z0-9\s]/g, '').trim();
    }

    function highlightMatch(text, term) {
        var regex = new RegExp('(' + term + ')', 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    $.ajax({
        url: './update_cache.php',
        type: 'GET',
        cache: false,
        success: function(data) {
            console.log("Cache update triggered: " + data);
        },
        error: function(xhr, status, error) {
            console.log("Cache update failed: " + error);
        }
    });

    $("#searchtext").on('keydown', function(e) {
        var items = $("#seachDIV table tr");
        if (items.length > 0 && (e.key === "ArrowDown" || e.key === "ArrowUp" || e.key === "Enter")) {
            if (e.key === "ArrowDown") {
                e.preventDefault();
                activeIndex = (activeIndex + 1) % items.length;
                items.removeClass('active');
                $(items[activeIndex]).addClass('active');
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                activeIndex = (activeIndex - 1 + items.length) % items.length;
                items.removeClass('active');
                $(items[activeIndex]).addClass('active');
            } else if (e.key === "Enter") {
                e.preventDefault();
                if (activeIndex >= 0) {
                    var $selected = $(items[activeIndex]);
                    var request = $selected.data("file");
                    var searchTerm = $selected.data("text");
                    $("#searchtext").val(searchTerm);
                    selectionMade = true;
                    $("#searchkey").val(request);
                    $("#seachDIV").hide().html("");
                }
            }
        }
    });

    $("#searchtext").keyup(function(e){
        if(["ArrowDown", "ArrowUp", "Enter"].includes(e.key)) return;
        var searchtext = $(this).val();
        lastSearchTerm = searchtext;
        activeIndex = -1;
        if(searchtext.length < 2){
            $("#seachDIV").hide().html("");
            return;
        }
        if(selectionMade) return;
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function(){
            $("#seachDIV").show().html('<div class="spinner"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            if(currentRequest !== null) {
                currentRequest.abort();
            }
            currentRequest = $.ajax({
                url: './pre_load.php',
                type: 'POST',
                data: { searchtext: searchtext },
                cache: false,
                success: function(data){
                    if($("#searchtext").val() === lastSearchTerm){
                        var $table = $(data);
                        var normalizedSearch = normalize(lastSearchTerm);
                        var rows = $table.find('td').map(function(){
                            var text = $(this).data("text");
                            var file = $(this).data("file");
                            var normalizedText = normalize(text);
                            var songTitle = text;
                            var artistName = "";

                            if (text.indexOf(" - ") > -1) {
                                var parts = text.split(" - ");
                                songTitle = parts[0];
                                artistName = parts[1];
                            }

                            return {
                                row: $(this).closest('tr'),
                                songTitle: songTitle,
                                artistName: artistName,
                                normalizedSongTitle: normalize(songTitle),
                                normalizedArtistName: normalize(artistName),
                                file: file,
                                text: text
                            };
                        }).get();

                        
                        rows.sort((a, b) => {
                            var aExactMatch = a.normalizedSongTitle === normalizedSearch;
                            var bExactMatch = b.normalizedSongTitle === normalizedSearch;
                            var aStartsWith = a.normalizedSongTitle.startsWith(normalizedSearch);
                            var bStartsWith = b.normalizedSongTitle.startsWith(normalizedSearch);
                            var aContains = a.normalizedSongTitle.includes(normalizedSearch);
                            var bContains = b.normalizedSongTitle.includes(normalizedSearch);
                            
                            
                            if (bExactMatch - aExactMatch !== 0) return bExactMatch - aExactMatch;

                            
                            if (bStartsWith - aStartsWith !== 0) return bStartsWith - aStartsWith;

                            
                            if (bContains - aContains !== 0) return bContains - aContains;

                            
                            return a.normalizedSongTitle.localeCompare(b.normalizedSongTitle);
                        });

                        if(rows.length > 0){
                            var html = '<table class="table table-hover mb-0">';
                            rows.forEach(function(item){
                                var highlightedSong = highlightMatch(item.songTitle, lastSearchTerm);
                                var highlightedArtist = item.artistName ? highlightMatch(item.artistName, lastSearchTerm) : "";
                                html += '<tr data-text="' + item.text + '" data-file="' + item.file + '">';
                                html += '<td>';
                                html += '<div class="fw-bold suggestion-song">' + highlightedSong + '</div>';
                                if (highlightedArtist) {
                                    html += '<div class="text-muted suggestion-artist">' + highlightedArtist + '</div>';
                                }
                                html += '</td>';
                                html += '</tr>';
                            });
                            html += '</table>';
                            $("#seachDIV").html(html).show();
                        } else {
                            $("#seachDIV").html('<div class="p-2">No relevant results.</div>').show();
                        }
                    }
                },
                complete: function(){
                    currentRequest = null;
                }
            });
        }, 100);
    });

    $("#searchtext").on('input', function(){
        selectionMade = false;
    });

    $("#seachDIV").on("mousedown", "tr", function(){
        var request = $(this).data("file");
        var searchTerm = $(this).data("text");
        $("#searchtext").val(searchTerm);
        selectionMade = true;
        $("#searchkey").val(request);
        $("#seachDIV").hide().html("");
    });

    $(document).click(function(e){
        if(!$(e.target).closest("#seachDIV, #searchtext").length){
            $("#seachDIV").hide();
        }
    });
});
</script>
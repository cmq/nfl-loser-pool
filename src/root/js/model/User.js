function User(jsonObj) {
    var self = this,
        picks, badges,
        i;
    
    this.id       = types.int(globals.getFromObj(jsonObj, 'id', 0));
    this.username = types.string(globals.getFromObj(jsonObj, 'username', ''));
    this.badges   = [];
    this.picks    = [];
    
    picks = globals.getFromObj(jsonObj, 'picks', []);
    if ($.isArray(picks)) {
        for (i=0; i<picks.length; i++) {
            this.addPick(picks[i]);
        }
    }
    // KDHTODO add badges
    // KDHTODO extract other data, like stuff from loseruser (UserYear)
}


User.prototype.addPick = function(pickObj) {
    var p;
    if (pickObj instanceof Pick) {
        p = pickObj;
    } else {
        p = new Pick(pickObj);
    }
    this.picks.push(p);
    p.user = this;
};

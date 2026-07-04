//
//  BusinessLogic.swift
//  Remote Widget
//
//  Created by Peter Popovec on 11/04/2026.
//

import MagicUI

struct BusinessLogic {
    static let shared = BusinessLogic()
    
    private init() {}
    
    var currentUser: String {
        AppEnvironment.env.value(for: "kumMode")?.stringValue ?? "petres"
    }
    
    var widgetURL: String {
        "\(Config.widgetUrlBase)/\(currentUser).php"
    }
}

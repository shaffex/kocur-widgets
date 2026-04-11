//
//  BusinessLogic.swift
//  Remote Widget
//
//  Created by Peter Popovec on 11/04/2026.
//

import MagicUiFramework

struct BusinessLogic {
    static let shared = BusinessLogic()
    
    private init() {}
    
    var currentUser: String {
        SxMagicVariables.shared.value(forKey: "kumMode") as? String ?? "petres"
    }
    
    var widgetURL: String {
        "\(Config.widgetUrlBase)/\(currentUser).php"
    }
}

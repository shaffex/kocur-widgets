//
//  Remote_WidgetApp.swift
//  Remote Widget
//
//  Created by Peter Popovec on 02/04/2026.
//

import SwiftUI

@main
struct Remote_WidgetApp: App {
    var body: some Scene {
        WindowGroup {
            ContentView()
                .onOpenURL { url in
                                    // Now open it in Safari / browser
                                    UIApplication.shared.open(url)
                                }
        }
    }
}

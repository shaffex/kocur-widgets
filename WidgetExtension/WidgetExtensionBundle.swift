//
//  WidgetExtensionBundle.swift
//  WidgetExtension
//
//  Created by Peter Popovec on 02/04/2026.
//

import WidgetKit
import SwiftUI
import MagicWidget
import AppIntents

@main
struct WidgetExtensionBundle: WidgetBundle, AppIntentsPackage {
    //MARK: This is important — it is declared in MagicWidget framework and must be referenced from the widget extension in order for button intent to work
    static var includedPackages: [any AppIntentsPackage.Type] {
        [MyFrameworkAppIntents.self]
    }
    
    init() {
        MagicUiView.installViewPlugin(name: "button2", plugin: SxView_OpenUrlIntentButton.self)
    }
    
    var body: some Widget {
        MyCustomNetworkWidget()
        SimpleLiveActivityWidget()
    }
}

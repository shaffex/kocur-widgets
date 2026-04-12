//
//  ContentView.swift
//  Remote Widget
//
//  Created by Peter Popovec on 02/04/2026.
//

import SwiftUI
import MagicUiFramework

struct ContentView: View {
    init() {
        // actions
        MagicUiView.installActionPlugin(name: "updateVariablesToServer", plugin: SxAction_updateVariablesToServer.self)
        
        MagicUiView.installActionPlugin(name: "sendMessage", plugin: SxAction_sendMessage.self)

        MagicUiView.installActionPlugin(name: "setVariable", plugin: SxAction_setVariable.self)

        MagicUiView.installActionPlugin(name: "updateWidgetsOnAllDevices", plugin: SxAction_updateWidgetsOnAllDevices.self)

        MagicUiView.installActionPlugin(name: "logStatusUpdate", plugin: SxAction_logStatusUpdate.self)

        MagicUiView.installActionPlugin(name: "reloadAllTimelines", plugin: SxAction_reloadAllTimelines.self)
        
        // live activity
        MagicUiView.installActionPlugin(name: "startLiveActivity", plugin: SxAction_startLiveActivity.self)
        MagicUiView.installActionPlugin(name: "updateLiveActivity", plugin: SxAction_updateLiveActivity.self)
        
        
        // modifiers
        MagicUiView.installModifierPlugin(name: "hideKeyboardOnTap", plugin: Modifier_hideKeyboardOnTap.self)
        MagicUiView.installModifierPlugin(name: "contentShapeRect", plugin: Modifier_contentShapeRect.self)
    }
    
    var body: some View {
        MagicUiView(resource: "Main")
            .onFirstAppear {
                SxEnvironmentObject.shared.setValue("fff", forKey: "DEVICE_UUID")
                SxMagicVariables.shared.setValue("MIW", forKey: "DEVICE_UUID")
                //SxMagicVariables.shared.setValue("MIW", forKey: "DEVICE_UUID")
                
                // we can update only when view is loaded
                DispatchQueue.main.asyncAfter(deadline: .now() + 0.01) {
                    SxEnvironmentObject.shared.setValue( UIDevice.current.identifierForVendor?.uuidString ?? "N/A", forKey: "DEVICE_UUID")
                }
            }
    }
}

#Preview {
    ContentView()
}

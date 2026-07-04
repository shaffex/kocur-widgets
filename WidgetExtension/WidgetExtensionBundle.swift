//
//  WidgetExtensionBundle.swift
//  WidgetExtension
//
//  Created by Peter Popovec on 02/04/2026.
//

import WidgetKit
import SwiftUI

@main
struct WidgetExtensionBundle: WidgetBundle {
    var body: some Widget {
        MagicUiWidget()
        SimpleLiveActivityWidget()
    }
}

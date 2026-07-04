//
//  AppIntent.swift
//  MagicUiWidget
//
//  Created by Peter Popovec on 03/06/2026.
//

import WidgetKit
import AppIntents

struct ConfigurationAppIntent: WidgetConfigurationIntent {
    static var title: LocalizedStringResource { "Magic UI Widget" }
    static var description: IntentDescription { "Configure the Magic UI widget source and refresh interval." }

    static var parameterSummary: some ParameterSummary {
        Summary {
            \.$widgetURL
            \.$refreshInterval
        }
    }

    // default widget
    //@Parameter(title: "Widget URL", default: "https://magic-ui.com/V2/Widgets/test.xml")
    
    @Parameter(title: "Widget URL", default: "https://magic-ui.com/KumWidgets/petres.php")
    
    
    var widgetURL: String

    @Parameter(title: "Refresh Interval (minutes)", default: 30)
    var refreshInterval: Int
}

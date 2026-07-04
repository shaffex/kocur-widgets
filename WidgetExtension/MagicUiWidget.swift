//
//  MagicUiWidget.swift
//  MagicUiWidget
//
//  Created by Peter Popovec on 03/06/2026.
//

import WidgetKit
import SwiftUI
import MagicUI

struct Provider: AppIntentTimelineProvider {
    let kind: String

    func placeholder(in context: Context) -> MyWidgetTimelineEntry {
        //SimpleEntry(date: Date(), configuration: ConfigurationAppIntent())
        MyWidgetTimelineEntry(date: Date(), configuration: ConfigurationAppIntent(), family: context.family, widgetPostFix: "", xml: "<text>Kocur: placeholder</text>")
    }

    func snapshot(for configuration: ConfigurationAppIntent, in context: Context) async -> MyWidgetTimelineEntry {
        MyWidgetTimelineEntry(date: Date(), configuration: configuration, family: context.family, widgetPostFix: "", xml: "<text>Kocur: snapshot</text>")
    }
    
    func timeline(for configuration: ConfigurationAppIntent, in context: Context) async -> Timeline<MyWidgetTimelineEntry> {

        let refreshInterval = configuration.refreshInterval
        let widgetURL = configuration.widgetURL + "?family=\(context.family)"

        print("timeline: refreshInterval: \(refreshInterval) family: \(context.family), widgetURL: \(widgetURL)")

        do {
            let entry = try await NetworkService.fetchEntry(urlString: widgetURL, kind: kind, family: context.family, configuration: configuration)
            let nextRefresh = Calendar.current.date(byAdding: .minute, value: refreshInterval, to: .now)!
            return Timeline(entries: [entry], policy: .after(nextRefresh))
        } catch {
            print("timeline fetch failed: \(error) — retrying in 5 minutes")
            let retry = Calendar.current.date(byAdding: .minute, value: 5, to: .now)!
            return Timeline(entries: [], policy: .after(retry))
        }
    }

//    func relevances() async -> WidgetRelevances<ConfigurationAppIntent> {
//        // Generate a list containing the contexts this widget is relevant in.
//    }
}

struct MagicUiWidgetEntryView : View {
    var entry: Provider.Entry

    @StateObject private var environment = MagicEnvironment(
        [
            "count": .number(3),
        ],
        persistenceKey: "magicui.demo"
    )
    
    var body: some View {
        let xml = NetworkService.loadXML(kind: "MagicUiWidget", family: entry.family) ?? entry.xml
        MagicUiView(string: xml, environment: environment)
    }
}

struct MagicUiWidget: Widget {
    let kind: String = "MagicUiWidgetMac"

    var body: some WidgetConfiguration {
        AppIntentConfiguration(kind: kind, intent: ConfigurationAppIntent.self, provider: Provider(kind: kind)) { entry in
            MagicUiWidgetEntryView(entry: entry)
                .containerBackground(.fill.tertiary, for: .widget)
        }
        .configurationDisplayName("Magic UI")
        .description("Displays Magic UI content from a configurable URL.")
        .pushHandler(MagicUiWidgetPushHandler.self)
    }
}

extension ConfigurationAppIntent {
    fileprivate static var previewDefault: ConfigurationAppIntent {
        let intent = ConfigurationAppIntent()
        intent.widgetURL = "https://magic-ui.com/V2/Widgets/test.xml"
        intent.refreshInterval = 30
        return intent
    }
    
    fileprivate static var previewFastRefresh: ConfigurationAppIntent {
        let intent = ConfigurationAppIntent()
        intent.widgetURL = "https://magic-ui.com/V2/Widgets/test.xml"
        intent.refreshInterval = 5
        return intent
    }
}

#Preview(as: .systemSmall) {
    MagicUiWidget()
} timeline: {
    MyWidgetTimelineEntry(date: .now, configuration: .previewDefault, family: .systemSmall, widgetPostFix: "", xml: "<circle/>")
    MyWidgetTimelineEntry(date: .now, configuration: .previewFastRefresh, family: .systemSmall, widgetPostFix: "", xml: "<circle/>")
}
